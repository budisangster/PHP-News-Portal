<?php

class Cache {
    private $cache_path;
    private $cache_expiry;

    public function __construct($cache_path = 'cache', $cache_expiry = 3600) {
        $this->cache_path = $cache_path;
        $this->cache_expiry = $cache_expiry;

        if (!is_dir($this->cache_path)) {
            mkdir($this->cache_path, 0755, true);
        }
    }

    public function get($key) {
        $filename = $this->cache_path . '/' . md5($key) . '.cache';

        if (file_exists($filename) && (time() - filemtime($filename) < $this->cache_expiry)) {
            return unserialize(file_get_contents($filename));
        }

        return false;
    }

    public function set($key, $data) {
        $filename = $this->cache_path . '/' . md5($key) . '.cache';
        return file_put_contents($filename, serialize($data));
    }

    public function delete($key) {
        $filename = $this->cache_path . '/' . md5($key) . '.cache';
        if (file_exists($filename)) {
            return unlink($filename);
        }
        return false;
    }

    public function clear() {
        $files = glob($this->cache_path . '/*.cache');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
