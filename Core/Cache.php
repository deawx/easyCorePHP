<?php

namespace Core;

class Cache {
    private string $cachePath;

    public function __construct() {
        $this->cachePath = __DIR__ . '/../storage/cache/';
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0777, true);
        }
    }

    public function get(string $key, $default = null) {
        $filename = $this->getFilename($key);

        if (!file_exists($filename)) {
            return $default;
        }

        $data = file_get_contents($filename);
        $cache = json_decode($data, true);

        if (!$cache || (isset($cache['expires_at']) && $cache['expires_at'] < time())) {
            $this->delete($key);
            return $default;
        }

        return $cache['value'];
    }

    public function put(string $key, $value, int $seconds = 60): bool {
        $filename = $this->getFilename($key);

        $cache = [
            'value' => $value,
            'expires_at' => time() + $seconds
        ];

        return file_put_contents($filename, json_encode($cache)) !== false;
    }

    public function delete(string $key): bool {
        $filename = $this->getFilename($key);

        if (file_exists($filename)) {
            return unlink($filename);
        }

        return true;
    }

    private function getFilename(string $key): string {
        return $this->cachePath . md5($key) . '.cache';
    }
}