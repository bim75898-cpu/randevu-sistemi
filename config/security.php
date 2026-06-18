<?php
class Security {
    private static $pdo = null;
    private static $ip = null;

    const RATE_LIMIT_WINDOW = 60;
    const RATE_LIMIT_MAX_REQUESTS = 30;
    const BRUTE_FORCE_MAX_ATTEMPTS = 5;
    const BRUTE_FORCE_WINDOW = 900;
    const BLOCK_DURATION = 3600;
    const SESSION_TIMEOUT = 1800;

    public static function init($pdo) {
        self::$pdo = $pdo;
        self::$ip = self::getClientIP();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        self::checkIPBlocked();
        self::enforceRateLimit();
        self::setSecurityHeaders();
        self::regenerateSession();
    }

    public static function getClientIP() {
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

    public static function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCSRFToken($token) {
        if (empty($_SESSION['csrf_token']) || empty($token)) return false;
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function getHoneypotField() {
        $name = 'website_' . substr(md5($_SESSION['csrf_token'] ?? ''), 0, 8);
        return '<div style="position:absolute;left:-9999px" aria-hidden="true">
            <input type="text" name="' . $name . '" tabindex="-1" autocomplete="off">
        </div>';
    }

    public static function checkHoneypot() {
        $name = 'website_' . substr(md5($_SESSION['csrf_token'] ?? ''), 0, 8);
        if (!empty($_POST[$name])) {
            self::logSecurityEvent('honeypot', 'Bot detected via honeypot');
            self::blockIP(86400);
            self::terminate('Bot detected');
        }
    }

    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        $data = str_replace(["\0", "\r"], '', $data);
        $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $data);
        return $data;
    }

    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && strlen($email) <= 254;
    }

    public static function validatePhone($phone) {
        return preg_match('/^[+\d][\d\s\-()]{7,20}$/', $phone) === 1;
    }

    public static function validateDate($date) {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    public static function validateTime($time) {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time) === 1;
    }

    private static function checkIPBlocked() {
        $stmt = self::$pdo->prepare(
            "SELECT expires_at FROM blocked_ips WHERE ip = ? AND (expires_at IS NULL OR expires_at > NOW())"
        );
        $stmt->execute([self::$ip]);
        $block = $stmt->fetch();

        if ($block) {
            if ($block['expires_at'] === null) {
                self::terminate('Erişiminiz engellendi.');
            }
            self::terminate('Erişiminiz geçici olarak engellendi. Lütfen daha sonra tekrar deneyin.');
        }
    }

    private static function enforceRateLimit() {
        $windowStart = date('Y-m-d H:i:s', time() - self::RATE_LIMIT_WINDOW);
        $endpoint = $_SERVER['REQUEST_URI'] ?? 'unknown';

        $stmt = self::$pdo->prepare(
            "SELECT COUNT(*) FROM rate_limits WHERE ip = ? AND endpoint = ? AND created_at >= ?"
        );
        $stmt->execute([self::$ip, $endpoint, $windowStart]);
        $count = $stmt->fetchColumn();

        if ($count >= self::RATE_LIMIT_MAX_REQUESTS) {
            self::logSecurityEvent('rate_limit', "Rate limit exceeded: $count requests in " . self::RATE_LIMIT_WINDOW . "s");
            self::terminate('Çok fazla istek gönderdiniz. Lütfen bekleyin.');
        }

        $stmt = self::$pdo->prepare(
            "INSERT INTO rate_limits (ip, endpoint) VALUES (?, ?)"
        );
        $stmt->execute([self::$ip, $endpoint]);

        $stmt = self::$pdo->prepare(
            "DELETE FROM rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL ? SECOND)"
        );
        $stmt->execute([self::RATE_LIMIT_WINDOW * 2]);
    }

    public static function checkBruteForce() {
        $windowStart = date('Y-m-d H:i:s', time() - self::BRUTE_FORCE_WINDOW);
        $stmt = self::$pdo->prepare(
            "SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at >= ?"
        );
        $stmt->execute([self::$ip, $windowStart]);
        $count = $stmt->fetchColumn();

        if ($count >= self::BRUTE_FORCE_MAX_ATTEMPTS) {
            self::blockIP(self::BLOCK_DURATION);
            self::logSecurityEvent('brute_force', "Brute force detected: $count attempts");
            return false;
        }
        return true;
    }

    public static function logLoginAttempt($username, $success) {
        $stmt = self::$pdo->prepare(
            "INSERT INTO login_attempts (ip, username, success) VALUES (?, ?, ?)"
        );
        $stmt->execute([self::$ip, $username, $success ? 1 : 0]);
    }

    public static function blockIP($duration = null) {
        $expires = $duration ? date('Y-m-d H:i:s', time() + $duration) : null;
        $stmt = self::$pdo->prepare(
            "INSERT INTO blocked_ips (ip, expires_at) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE expires_at = VALUES(expires_at), blocked_at = NOW()"
        );
        $stmt->execute([self::$ip, $expires]);
    }

    public static function logSecurityEvent($type, $details) {
        try {
            $stmt = self::$pdo->prepare(
                "INSERT INTO security_log (ip, event_type, details) VALUES (?, ?, ?)"
            );
            $stmt->execute([self::$ip, $type, $details]);
        } catch (Exception $e) {
        }
    }

    public static function regenerateSession() {
        if (!isset($_SESSION['_created'])) {
            $_SESSION['_created'] = time();
            session_regenerate_id(true);
        } elseif (time() - $_SESSION['_created'] > self::SESSION_TIMEOUT) {
            session_destroy();
            session_start();
            $_SESSION['_created'] = time();
            session_regenerate_id(true);
        } elseif (mt_rand(1, 10) === 1) {
            session_regenerate_id(true);
        }
    }

    private static function setSecurityHeaders() {
        $headers = [
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=(), payment=()',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' https://cdn.tailwindcss.com 'unsafe-inline'; style-src 'self' https://cdn.tailwindcss.com https://fonts.googleapis.com 'unsafe-inline'; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; form-action 'self'; frame-ancestors 'self'",
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ];

        foreach ($headers as $key => $value) {
            if (!headers_sent()) {
                header("$key: $value");
            }
        }
    }

    public static function terminate($message = 'Erişim reddedildi.') {
        http_response_code(403);
        echo json_encode(['error' => $message]);
        exit;
    }

    public static function verifyAdminSession() {
        if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            header('Location: login.php');
            exit;
        }
        if (!isset($_SESSION['admin_ip'])) {
            $_SESSION['admin_ip'] = self::$ip;
        } elseif ($_SESSION['admin_ip'] !== self::$ip) {
            session_destroy();
            header('Location: login.php');
            exit;
        }
        if (!isset($_SESSION['admin_user_agent'])) {
            $_SESSION['admin_user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        } elseif ($_SESSION['admin_user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            session_destroy();
            header('Location: login.php');
            exit;
        }
    }
}
