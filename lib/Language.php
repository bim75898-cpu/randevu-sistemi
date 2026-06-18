<?php
class Language {
    private static $strings = [];
    private static $currentLang = 'tr';
    private static $initialized = false;
    private static $pdo = null;

    public static function init($pdo = null) {
        if (self::$initialized) return;
        self::$pdo = $pdo;
        $lang = self::detect();
        self::load($lang);
        self::$initialized = true;
    }

    public static function detect() {
        if (self::$pdo) {
            try {
                require_once __DIR__ . '/Settings.php';
                $default = Settings::get('app_language', 'tr');
            } catch (\Exception $e) {
                $default = 'tr';
            }
        } else {
            $default = 'tr';
        }
        if (isset($_SESSION['lang'])) return $_SESSION['lang'];
        if ($default !== 'auto') return $default;
        $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'tr', 0, 2);
        return in_array($browserLang, ['tr', 'en']) ? $browserLang : $default;
    }

    public static function load($lang) {
        self::$currentLang = in_array($lang, ['tr', 'en']) ? $lang : 'tr';
        $file = __DIR__ . '/lang/' . self::$currentLang . '.php';
        if (file_exists($file)) {
            self::$strings = require $file;
        } else {
            self::$strings = require __DIR__ . '/lang/tr.php';
            self::$currentLang = 'tr';
        }
    }

    public static function set($lang) {
        $_SESSION['lang'] = $lang;
        self::load($lang);
    }

    public static function get($key, $default = null) {
        return self::$strings[$key] ?? $default ?? $key;
    }

    public static function current() {
        return self::$currentLang;
    }

    public static function all() {
        return self::$strings;
    }

    public static function selector($name = 'lang', $selected = null) {
        $current = $selected ?? self::$currentLang;
        $langs = ['tr' => 'Türkçe', 'en' => 'English', 'auto' => self::get('language_auto')];
        $html = '<select name="' . $name . '" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">';
        foreach ($langs as $code => $label) {
            $sel = $code === $current ? ' selected' : '';
            $html .= '<option value="' . $code . '"' . $sel . '>' . $label . '</option>';
        }
        return $html . '</select>';
    }
}

function _t($key, $default = null) {
    return Language::get($key, $default);
}