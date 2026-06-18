<?php
class Settings {
    private static $cache = null;
    private static $pdo = null;

    public static function init($pdo) {
        self::$pdo = $pdo;
    }

    public static function get($key, $default = '') {
        self::loadAll();
        return self::$cache[$key] ?? $default;
    }

    public static function set($key, $value) {
        if (!self::$pdo) return;
        $stmt = self::$pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$key, $value, $value]);
        if (self::$cache !== null) {
            self::$cache[$key] = $value;
        }
    }

    public static function getAll() {
        self::loadAll();
        return self::$cache;
    }

    private static function loadAll() {
        if (self::$cache !== null || !self::$pdo) return;
        self::$cache = [];
        $stmt = self::$pdo->query("SELECT setting_key, setting_value FROM settings");
        while ($row = $stmt->fetch()) {
            self::$cache[$row['setting_key']] = $row['setting_value'];
        }
    }

    public static function getMailConfig() {
        return [
            'smtp_host' => self::get('mail_smtp_host', 'smtp.gmail.com'),
            'smtp_port' => (int)self::get('mail_smtp_port', '587'),
            'smtp_secure' => self::get('mail_smtp_secure', 'tls'),
            'smtp_auth' => self::get('mail_smtp_auth', '1') === '1',
            'smtp_username' => self::get('mail_smtp_username', ''),
            'smtp_password' => self::get('mail_smtp_password', ''),
            'from_email' => self::get('mail_from_email', ''),
            'from_name' => self::get('mail_from_name', 'Randevu Sistemi'),
        ];
    }

    public static function getSmsConfig() {
        return [
            'provider' => self::get('sms_provider', 'log'),
            'twilio_sid' => self::get('sms_twilio_sid', ''),
            'twilio_token' => self::get('sms_twilio_token', ''),
            'twilio_from' => self::get('sms_twilio_from', ''),
            'netgsm_user' => self::get('sms_netgsm_user', ''),
            'netgsm_pass' => self::get('sms_netgsm_pass', ''),
            'netgsm_msgheader' => self::get('sms_netgsm_msgheader', ''),
        ];
    }

    public static function getPaymentConfig() {
        return [
            'provider' => self::get('payment_provider', 'iyzico'),
            'iyzico_api_key' => self::get('payment_iyzico_api_key', 'sandbox-'),
            'iyzico_secret_key' => self::get('payment_iyzico_secret_key', 'sandbox-'),
            'iyzico_sandbox' => self::get('payment_iyzico_sandbox', '1') === '1',
            'iyzico_base_url' => self::get('payment_iyzico_base_url', 'https://sandbox-api.iyzipay.com'),
        ];
    }
}
