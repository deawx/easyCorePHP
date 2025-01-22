<?php

namespace Core\Auth;

use Core\Cache;

class RateLimiter {
    private Cache $cache;
    private int $maxAttempts;
    private int $decayMinutes;

    public function __construct(int $maxAttempts = 5, int $decayMinutes = 1) {
        $this->cache = new Cache();
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
    }

    public function tooManyAttempts(string $key): bool {
        $attempts = $this->cache->get($key, 0);
        return $attempts >= $this->maxAttempts;
    }

    public function hit(string $key): void {
        $attempts = $this->cache->get($key, 0) + 1;
        $this->cache->put($key, $attempts, $this->decayMinutes * 60);
    }

    public function clear(string $key): void {
        $this->cache->delete($key);
    }
}