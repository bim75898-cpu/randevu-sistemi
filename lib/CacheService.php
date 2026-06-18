<?php
class CacheService {
    private static $instance = null;
    private $type = 'file';
    private $prefix = 'randv_';
    private $ttl = 3600;
    private $server = '127.0.0.1';
    private $port = 6379;
    private $redis = null;
    private $memcache = null;
    private $cacheDir = '';

    public static function init($type = null) {
        if (self::$instance !== null) return self::$instance;
        self::$instance = new self();
        try {
            require_once __DIR__ . '/Settings.php';
            $type = $type ?? Settings::get('cache_type', 'file');
            self::$instance->type = in_array($type, ['redis', 'memcache', 'file']) ? $type : 'file';
            self::$instance->prefix = Settings::get('cache_prefix', 'randv_');
            self::$instance->server = Settings::get('cache_server', '127.0.0.1');
            self::$instance->port = (int)Settings::get('cache_port', $type === 'memcache' ? 11211 : 6379);
            self::$instance->ttl = (int)Settings::get('cache_ttl', 3600);
        } catch (\Exception $e) {
            self::$instance->type = 'file';
        }
        self::$instance->cacheDir = __DIR__ . '/../cache/';
        if (!is_dir(self::$instance->cacheDir)) {
            @mkdir(self::$instance->cacheDir, 0755, true);
        }
        self::$instance->connect();
        return self::$instance;
    }

    private function connect() {
        if ($this->type === 'redis' && class_exists('\Redis')) {
            try {
                $this->redis = new \Redis();
                $this->redis->connect($this->server, $this->port, 2);
            } catch (\Exception $e) {
                $this->type = 'file';
            }
        }
        if ($this->type === 'memcache' && class_exists('\Memcached')) {
            try {
                $this->memcache = new \Memcached();
                $this->memcache->addServer($this->server, $this->port);
            } catch (\Exception $e) {
                $this->type = 'file';
            }
        }
    }

    public function get($key) {
        $k = $this->prefix . $key;
        if ($this->type === 'redis' && $this->redis) {
            $val = $this->redis->get($k);
            return $val !== false ? @unserialize($val) : null;
        }
        if ($this->type === 'memcache' && $this->memcache) {
            $val = $this->memcache->get($k);
            return $val !== false ? @unserialize($val) : null;
        }
        return $this->fileGet($k);
    }

    public function set($key, $value, $ttl = null) {
        $k = $this->prefix . $key;
        $ttl = $ttl ?? $this->ttl;
        if ($this->type === 'redis' && $this->redis) {
            return $this->redis->setex($k, $ttl, serialize($value));
        }
        if ($this->type === 'memcache' && $this->memcache) {
            return $this->memcache->set($k, serialize($value), $ttl);
        }
        return $this->fileSet($k, $value, $ttl);
    }

    public function delete($key) {
        $k = $this->prefix . $key;
        if ($this->type === 'redis' && $this->redis) return $this->redis->del($k) > 0;
        if ($this->type === 'memcache' && $this->memcache) return $this->memcache->delete($k);
        return $this->fileDelete($k);
    }

    public function remember($key, $ttl, callable $callback) {
        $cached = $this->get($key);
        if ($cached !== null) return $cached;
        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    public function flush() {
        if ($this->type === 'redis' && $this->redis) return $this->redis->flushAll();
        if ($this->type === 'memcache' && $this->memcache) return $this->memcache->flush();
        $this->clearDirectory($this->cacheDir);
        return true;
    }

    public function type() { return $this->type; }
    public function available($type) {
        if ($type === 'redis') return class_exists('\Redis');
        if ($type === 'memcache') return class_exists('\Memcached');
        return true;
    }
    public function types() {
        $types = ['file' => true, 'redis' => false, 'memcache' => false];
        if (class_exists('\Redis')) $types['redis'] = true;
        if (class_exists('\Memcached')) $types['memcache'] = true;
        return $types;
    }

    private function fileGet($key) {
        $file = $this->cacheDir . md5($key) . '.cache';
        if (!file_exists($file)) return null;
        $data = @file_get_contents($file);
        if (!$data) return null;
        $parts = explode("\n", $data, 2);
        if (count($parts) !== 2) return null;
        $expiry = (int)$parts[0];
        if ($expiry > 0 && time() > $expiry) {
            @unlink($file);
            return null;
        }
        return @unserialize($parts[1]);
    }

    private function fileSet($key, $value, $ttl) {
        $file = $this->cacheDir . md5($key) . '.cache';
        $expiry = $ttl > 0 ? time() + $ttl : 0;
        $data = $expiry . "\n" . serialize($value);
        return @file_put_contents($file, $data, LOCK_EX) !== false;
    }

    private function fileDelete($key) {
        $file = $this->cacheDir . md5($key) . '.cache';
        if (file_exists($file)) return @unlink($file);
        return true;
    }

    private function clearDirectory($dir) {
        $files = glob($dir . '*.cache');
        foreach ($files as $f) @unlink($f);
    }

    public function cleanup() {
        if ($this->type !== 'file') return;
        $files = glob($this->cacheDir . '*.cache');
        $now = time();
        foreach ($files as $f) {
            $data = @file_get_contents($f);
            if ($data) {
                $expiry = (int)explode("\n", $data, 2)[0];
                if ($expiry > 0 && $now > $expiry) @unlink($f);
            }
        }
    }
}