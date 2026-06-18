<?php
class ErrorHandler {
    private static $registered = false;
    private static $logDir = '';
    private static $sentryDsn = '';
    private static $logLevel = 2;

    const LEVEL_NONE = 0;
    const LEVEL_ERROR = 1;
    const LEVEL_ALL = 2;

    public static function register($pdo = null) {
        if (self::$registered) return;
        self::$logDir = __DIR__ . '/../logs/';
        if (!is_dir(self::$logDir)) @mkdir(self::$logDir, 0755, true);

        try {
            if ($pdo) {
                require_once __DIR__ . '/Settings.php';
                self::$sentryDsn = Settings::get('sentry_dsn', '');
                self::$logLevel = (int)Settings::get('error_log_level', 2);
            }
        } catch (\Exception $e) {
            self::$logLevel = 2;
        }

        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        self::$registered = true;
    }

    public static function handleException($e) {
        $message = $e->getMessage();
        $file = $e->getFile();
        $line = $e->getLine();
        $trace = $e->getTraceAsString();

        self::log("Exception: $message in $file:$line\n$trace");

        if (self::$sentryDsn && strpos(self::$sentryDsn, 'https://') === 0) {
            self::sendToSentry($message, $file, $line, $trace);
        }

        if (php_sapi_name() === 'cli') {
            echo "[ERROR] $message\n";
            return;
        }

        http_response_code(500);
        if (self::isAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Internal server error. Please try again.']);
            exit;
        }
        ob_get_level() && ob_clean();
        echo '<div style="padding:20px;background:#fee;border:1px solid #f99;border-radius:8px;margin:20px;font-family:sans-serif">';
        echo '<h2>Bir hata oluştu</h2>';
        echo '<p>Lütfen daha sonra tekrar deneyiniz.</p>';
        echo '</div>';
        exit;
    }

    public static function handleError($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) return false;

        if (self::$logLevel >= self::LEVEL_ALL) {
            $type = self::errorType($severity);
            self::log("$type: $message in $file:$line");
        }

        if (self::$sentryDsn && strpos(self::$sentryDsn, 'https://') === 0 && self::$logLevel >= self::LEVEL_ERROR) {
            self::sendToSentry($message, $file, $line, '');
        }

        return true;
    }

    public static function log($message) {
        if (self::$logLevel < self::LEVEL_ALL) return;
        $file = self::$logDir . 'error.log';
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    public static function getLog() {
        $file = self::$logDir . 'error.log';
        if (!file_exists($file)) return '';
        $size = filesize($file);
        if ($size > 1048576) {
            $contents = file_get_contents($file, false, null, $size - 1048576, 1048576);
            return "[LOG TRUNCATED - showing last 1MB]\n" . $contents;
        }
        return file_get_contents($file);
    }

    public static function clearLog() {
        $file = self::$logDir . 'error.log';
        if (file_exists($file)) @unlink($file);
    }

    public static function sendTestSentry() {
        if (!self::$sentryDsn) return 'Sentry DSN ayarlanmamış.';
        try {
            throw new \Exception('[Test] Sentry test mesajı');
        } catch (\Exception $e) {
            self::sendToSentry($e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
        }
        return 'Test gönderildi.';
    }

    private static function sendToSentry($message, $file, $line, $trace) {
        $dsn = self::$sentryDsn;
        if (!$dsn || strpos($dsn, 'https://') !== 0) return;

        $parsed = parse_url($dsn);
        $parts = explode('/', $parsed['path'] ?? '');
        $projectId = end($parts);
        $key = $parsed['user'] ?? '';
        $secret = $parsed['pass'] ?? '';
        $host = $parsed['host'] ?? '';

        $data = [
            'event_id' => str_replace('-', '', substr(uniqid(), 0, 32)),
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'level' => 'error',
            'logger' => 'php',
            'message' => $message,
            'culprit' => "$file:$line",
            'exception' => [['type' => 'Error', 'value' => $message, 'module' => $file]],
            'stacktrace' => ['frames' => [['filename' => $file, 'lineno' => $line]]],
            'tags' => [['server_name' => gethostname()]],
        ];
        if ($trace) {
            $frames = [];
            $lines = explode("\n", $trace);
            foreach ($lines as $t) {
                if (preg_match('/#(\d+)\s+(.*?)(?:\(.*?\))?:\s+(.*)/', $t, $m)) {
                    $frames[] = ['filename' => $m[2], 'function' => $m[3]];
                }
            }
            if ($frames) $data['stacktrace']['frames'] = $frames;
        }

        $payload = json_encode($data);
        $auth = "Sentry sentry_version=7, sentry_key=$key, sentry_secret=$secret";
        $url = "https://$host/api/$projectId/store/";

        $ch = @curl_init();
        if ($ch) {
            @curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    "X-Sentry-Auth: $auth",
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            @curl_exec($ch);
            @curl_close($ch);
        }
    }

    private static function errorType($severity) {
        $map = [
            E_ERROR => 'Fatal Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_DEPRECATED => 'Deprecated',
        ];
        return $map[$severity] ?? 'Unknown';
    }

    private static function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}