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

    /**
     * Attempt to execute an action
     *
     * @param string $key The rate limiting key
     * @param int $maxAttempts Maximum number of attempts allowed
     * @param int $decayMinutes Number of minutes until the rate limit resets
     * @return bool True if the attempt is allowed, false otherwise
     */
    public function attempt(string $key, int $maxAttempts = null, int $decayMinutes = null): bool {
        // Override instance defaults if provided
        $this->maxAttempts = $maxAttempts ?? $this->maxAttempts;
        $this->decayMinutes = $decayMinutes ?? $this->decayMinutes;

        // Check if too many attempts
        if ($this->tooManyAttempts($key)) {
            return false;
        }

        // Record the attempt
        $this->hit($key);

        return true;
    }

    /**
     * Get the number of attempts remaining
     *
     * @param string $key The rate limiting key
     * @return int Number of attempts remaining
     */
    public function retriesLeft(string $key): int {
        $attempts = $this->cache->get($key, 0);
        return max(0, $this->maxAttempts - $attempts);
    }
}