<?php
class BackupService {
    private static $backupDir = '';

    public static function init() {
        self::$backupDir = __DIR__ . '/../backups/';
        if (!is_dir(self::$backupDir)) @mkdir(self::$backupDir, 0755, true);
    }

    public static function create($label = 'manual') {
        self::init();
        $dbSettings = self::getDbSettings();
        if (!$dbSettings) return ['success' => false, 'error' => 'Veritabanı ayarları alınamadı.'];

        $filename = 'backup_' . $label . '_' . date('Ymd_His') . '.sql.gz';
        $filepath = self::$backupDir . $filename;

        $dump = self::mysqldump($dbSettings);
        if ($dump === false) return ['success' => false, 'error' => 'mysqldump başarısız oldu.'];

        $gz = @gzopen($filepath, 'w9');
        if (!$gz) return ['success' => false, 'error' => 'Dosya yazılamıyor: ' . $filepath];
        @gzwrite($gz, $dump);
        @gzclose($gz);

        self::cleanupOld();

        require_once __DIR__ . '/Settings.php';
        Settings::set('last_backup', date('Y-m-d H:i:s'));
        Settings::set('last_backup_file', $filename);

        return ['success' => true, 'file' => $filename, 'path' => $filepath, 'size' => filesize($filepath)];
    }

    public static function list() {
        self::init();
        $files = glob(self::$backupDir . '*.sql.gz');
        $backups = [];
        foreach ($files as $f) {
            $backups[] = [
                'name' => basename($f),
                'size' => filesize($f),
                'date' => date('Y-m-d H:i:s', filemtime($f)),
            ];
        }
        rsort($backups);
        return $backups;
    }

    public static function download($filename) {
        self::init();
        $file = self::$backupDir . basename($filename);
        if (!file_exists($file)) return false;
        header('Content-Type: application/gzip');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        return true;
    }

    public static function delete($filename) {
        self::init();
        $file = self::$backupDir . basename($filename);
        if (file_exists($file)) @unlink($file);
        return true;
    }

    public static function runAuto() {
        require_once __DIR__ . '/Settings.php';
        $enabled = Settings::get('backup_auto_enabled', '0');
        if ($enabled !== '1') return;

        $frequency = Settings::get('backup_frequency', 'daily');
        $lastFile = Settings::get('last_backup_file', '');

        if ($frequency === 'daily') {
            if ($lastFile) {
                $lastTime = filemtime(self::$backupDir . basename($lastFile));
                if ($lastTime && date('Y-m-d', $lastTime) === date('Y-m-d')) return;
            }
        } elseif ($frequency === 'weekly') {
            if ($lastFile) {
                $lastTime = filemtime(self::$backupDir . basename($lastFile));
                if ($lastTime && date('o-W', $lastTime) === date('o-W')) return;
            }
        } elseif ($frequency === 'monthly') {
            if ($lastFile) {
                $lastTime = filemtime(self::$backupDir . basename($lastFile));
                if ($lastTime && date('Y-m', $lastTime) === date('Y-m')) return;
            }
        } else {
            return;
        }

        self::create('auto');
    }

    private static function cleanupOld() {
        require_once __DIR__ . '/Settings.php';
        $retention = (int)Settings::get('backup_retention', '7');
        if ($retention <= 0) return;

        $files = glob(self::$backupDir . '*.sql.gz');
        $cutoff = time() - ($retention * 86400);
        foreach ($files as $f) {
            if (filemtime($f) < $cutoff) @unlink($f);
        }
    }

    private static function mysqldump($db) {
        $host = $db['host'];
        $port = $db['port'];
        $user = $db['user'];
        $pass = $db['pass'];
        $name = $db['name'];

        $cmd = sprintf(
            '"%s" --host=%s --port=%d --user=%s --password=%s %s 2>&1',
            self::findMysqldump(),
            escapeshellarg($host),
            (int)$port,
            escapeshellarg($user),
            escapeshellarg($pass),
            escapeshellarg($name)
        );

        $output = @shell_exec($cmd);
        if (!$output) {
            $output = @shell_exec(str_replace('--password=' . escapeshellarg($pass), '-p' . escapeshellarg($pass), $cmd));
        }

        return $output;
    }

    private static function findMysqldump() {
        $paths = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 5.7\\bin\\mysqldump.exe',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/opt/lampp/bin/mysqldump',
        ];
        foreach ($paths as $p) {
            if (file_exists($p)) return $p;
        }
        return 'mysqldump';
    }

    private static function getDbSettings() {
        $host = $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? '127.0.0.1';
        $port = $_ENV['DB_PORT'] ?? $_SERVER['DB_PORT'] ?? '3306';
        $user = $_ENV['DB_USER'] ?? $_SERVER['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASS'] ?? $_SERVER['DB_PASS'] ?? '';
        $name = $_ENV['DB_NAME'] ?? $_SERVER['DB_NAME'] ?? 'randevu_sistemi';

        if (defined('DB_HOST')) {
            $host = DB_HOST;
            $port = DB_PORT;
            $user = DB_USER;
            $pass = DB_PASS;
            $name = DB_NAME;
        }

        return ['host' => $host, 'port' => $port, 'user' => $user, 'pass' => $pass, 'name' => $name];
    }
}