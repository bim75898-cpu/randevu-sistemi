<?php
require_once __DIR__ . '/../lib/Settings.php';

class ApiAuth {
    private static $pdo = null;
    private static $ip = null;
    private static $rateLimitMax = 30;
    private static $rateLimitWindow = 60;
    private static $bruteForceMax = 10;
    private static $bruteForceWindow = 900;
    private static $blockDuration = 3600;
    private static $maxBodySize = 1048576; // 1MB

    public static function init($pdo) {
        self::$pdo = $pdo;
        self::$ip = self::getClientIP();
        self::$rateLimitMax = (int)Settings::get('api_rate_limit', '30');
    }

    public static function authenticate() {
        $enabled = Settings::get('api_enabled', '1');
        if ($enabled !== '1') {
            self::respond(403, ['error' => 'API disabled']);
        }

        self::checkIPBlocked();
        self::enforceRateLimit();

        $token = self::extractToken();
        if (!$token) {
            self::logSecurity('api_auth_fail', 'Missing authorization token from ' . self::$ip);
            self::respond(401, ['error' => 'Missing authorization token']);
        }

        $stored = Settings::get('api_key', '');
        if (!$stored || !hash_equals($stored, $token)) {
            self::logSecurity('api_auth_fail', 'Invalid token attempt from ' . self::$ip);
            self::trackBruteForce();
            self::respond(401, ['error' => 'Invalid token']);
        }

        self::clearBruteForce();
        return true;
    }

    private static function extractToken() {
        $header = '';

        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            $header = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        } elseif (!empty($_SERVER['Authorization'])) {
            $header = $_SERVER['Authorization'];
        } elseif (function_exists('apache_request_headers')) {
            $allHeaders = apache_request_headers();
            $header = $allHeaders['Authorization'] ?? $allHeaders['authorization'] ?? '';
        }

        if ($header && preg_match('/Bearer\s+(.+)/i', $header, $m)) {
            return $m[1];
        }
        return null;
    }

    public static function getJson() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
            if (strpos($contentType, 'application/json') === false && strpos($contentType, 'application/x-www-form-urlencoded') === false) {
                if (!empty($_POST)) return $_POST;
            }
        }

        $raw = file_get_contents('php://input');
        if (strlen($raw) > self::$maxBodySize) {
            self::respond(413, ['error' => 'Request body too large (max 1MB)']);
        }
        $data = json_decode($raw, true);
        return $data ?? [];
    }

    public static function sanitize($value) {
        if (is_array($value)) {
            return array_map([self::class, 'sanitize'], $value);
        }
        if (is_string($value)) {
            $value = str_replace(["\0", "\r"], '', $value);
            $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value);
        }
        return $value;
    }

    public static function validateNumericId($id, $field = 'ID') {
        if ($id === null || $id === '') return null;
        if (!ctype_digit((string)$id) && !is_int($id)) {
            self::respond(400, ['error' => "Invalid $field: must be a number"]);
        }
        return (int)$id;
    }

    public static function validateDate($date, $field = 'date') {
        if (empty($date)) return null;
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if (!$d || $d->format('Y-m-d') !== $date) {
            self::respond(400, ['error' => "Invalid $field: expected YYYY-MM-DD format"]);
        }
        return $date;
    }

    public static function validateTime($time, $field = 'time') {
        if (empty($time)) return null;
        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $time)) {
            self::respond(400, ['error' => "Invalid $field: expected HH:MM format"]);
        }
        return substr($time, 0, 5);
    }

    public static function validateEmail($email) {
        if (empty($email)) return '';
        $email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);
        if ($email === false || strlen($email) > 254) {
            self::respond(400, ['error' => 'Invalid email address']);
        }
        return $email;
    }

    public static function validatePhone($phone) {
        if (empty($phone)) return '';
        $phone = preg_replace('/[^\d+]/', '', $phone);
        if (!preg_match('/^\+?\d{7,15}$/', $phone)) {
            self::respond(400, ['error' => 'Invalid phone number']);
        }
        return $phone;
    }

    public static function validateStatus($status) {
        $allowed = ['pending', 'confirmed', 'cancelled', 'completed'];
        if ($status && !in_array($status, $allowed)) {
            self::respond(400, ['error' => 'Invalid status. Allowed: ' . implode(', ', $allowed)]);
        }
        return $status;
    }

    public static function validateFrequency($freq) {
        $allowed = ['weekly', 'biweekly', 'monthly'];
        if ($freq && !in_array($freq, $allowed)) {
            self::respond(400, ['error' => 'Invalid frequency. Allowed: ' . implode(', ', $allowed)]);
        }
        return $freq;
    }

    public static function generateKey() {
        return 'randv_' . bin2hex(random_bytes(24));
    }

    public static function respond($code, $data = []) {
        if (!headers_sent()) {
            http_response_code($code);
            header('Content-Type: application/json');
            self::securityHeaders();
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function requireMethod($method) {
        if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
            self::respond(405, ['error' => 'Method not allowed', 'allowed' => $method]);
        }
    }

    public static function cors() {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Max-Age: 86400');
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    private static function securityHeaders() {
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'no-referrer',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ];
        foreach ($headers as $k => $v) {
            header("$k: $v");
        }
    }

    private static function getClientIP() {
        $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        foreach ($headers as $h) {
            if (!empty($_SERVER[$h])) {
                $ips = explode(',', $_SERVER[$h]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    private static function checkIPBlocked() {
        if (!self::$pdo) return;
        $stmt = self::$pdo->prepare(
            "SELECT expires_at FROM blocked_ips WHERE ip = ? AND (expires_at IS NULL OR expires_at > NOW())"
        );
        $stmt->execute([self::$ip]);
        $block = $stmt->fetch();
        if ($block) {
            self::respond(429, ['error' => 'Too many requests. Your IP is temporarily blocked.']);
        }
    }

    private static function enforceRateLimit() {
        if (!self::$pdo) return;
        $windowStart = date('Y-m-d H:i:s', time() - self::$rateLimitWindow);
        $endpoint = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

        // Clean old entries
        self::$pdo->exec("DELETE FROM rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL " . (self::$rateLimitWindow * 2) . " SECOND)");

        $stmt = self::$pdo->prepare(
            "SELECT COUNT(*) FROM rate_limits WHERE ip = ? AND endpoint = ? AND created_at >= ?"
        );
        $stmt->execute([self::$ip, $endpoint, $windowStart]);
        $count = $stmt->fetchColumn();

        if ($count >= self::$rateLimitMax) {
            self::logSecurity('api_rate_limit', "Rate limit exceeded: $count requests in " . self::$rateLimitWindow . "s on $endpoint");
            self::respond(429, ['error' => 'Rate limit exceeded. Try again later.']);
        }

        $stmt = self::$pdo->prepare("INSERT INTO rate_limits (ip, endpoint) VALUES (?, ?)");
        $stmt->execute([self::$ip, $endpoint]);
    }

    private static function trackBruteForce() {
        if (!self::$pdo) return;
        $windowStart = date('Y-m-d H:i:s', time() - self::$bruteForceWindow);
        $stmt = self::$pdo->prepare(
            "SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND success = 0 AND attempted_at >= ?"
        );
        $stmt->execute([self::$ip, $windowStart]);
        $count = $stmt->fetchColumn();

        if ($count >= self::$bruteForceMax - 1) {
            $expires = date('Y-m-d H:i:s', time() + self::$blockDuration);
            $stmt = self::$pdo->prepare(
                "INSERT INTO blocked_ips (ip, expires_at) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE expires_at = VALUES(expires_at), blocked_at = NOW()"
            );
            $stmt->execute([self::$ip, $expires]);
            self::logSecurity('api_brute_force', "IP blocked for " . self::$blockDuration . "s after $count failed attempts");
        }

        $stmt = self::$pdo->prepare("INSERT INTO login_attempts (ip, username, success) VALUES (?, 'api', 0)");
        $stmt->execute([self::$ip]);
    }

    private static function clearBruteForce() {
        if (!self::$pdo) return;
        $stmt = self::$pdo->prepare("DELETE FROM login_attempts WHERE ip = ? AND success = 0");
        $stmt->execute([self::$ip]);
    }

    public static function logSecurity($type, $details) {
        try {
            if (!self::$pdo) return;
            $stmt = self::$pdo->prepare(
                "INSERT INTO security_log (ip, event_type, details) VALUES (?, ?, ?)"
            );
            $stmt->execute([self::$ip, $type, $details]);
        } catch (Exception $e) {}
    }
}
