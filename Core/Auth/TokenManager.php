<?php

namespace Core\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Core\Cache;

class TokenManager {
    private string $key;
    private int $accessTokenExpiry;
    private int $refreshTokenExpiry;
    private Cache $cache;

    private array $allowedAlgorithms = ['HS256', 'HS384', 'HS512'];
    private RateLimiter $rateLimiter;

    public function __construct() {
        // Load all configurations from environment variables
        $defaultKey = bin2hex(random_bytes(32)); // Generate a secure random key if not set
        $this->key = $_ENV['JWT_SECRET'] ?? $defaultKey;
        $this->accessTokenExpiry = (int)($_ENV['JWT_ACCESS_TOKEN_EXPIRY'] ?? 3600);
        $this->refreshTokenExpiry = (int)($_ENV['JWT_REFRESH_TOKEN_EXPIRY'] ?? 604800);

        // Validate key length
        if (strlen($this->key) < 32) {
            throw new \InvalidArgumentException('JWT secret key must be at least 32 characters long');
        }

        $this->cache = new Cache();
        $this->rateLimiter = new RateLimiter();
    }

    public function createTokenPair(array $userData): array {
        // Rate limiting check
        if (!$this->rateLimiter->attempt('token_creation:' . ($userData['sub'] ?? 'unknown'), 10, 60)) {
            throw new \RuntimeException('Too many token requests. Please try again later.');
        }

        // Validate required data
        if (!isset($userData['sub']) || !isset($userData['email'])) {
            throw new \InvalidArgumentException('User data must contain sub and email');
        }

        // Add jti (JWT ID) claim for token tracking
        $jti = bin2hex(random_bytes(16));
        $userData['jti'] = $jti;

        $accessToken = $this->createToken($userData, $this->accessTokenExpiry);
        $refreshToken = $this->createToken($userData, $this->refreshTokenExpiry, 'refresh');

        // Store refresh token hash for rotation validation
        $this->cache->put(
            "refresh_token:{$userData['sub']}:{$jti}",
            hash('sha256', $refreshToken),
            $this->refreshTokenExpiry
        );

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $this->accessTokenExpiry,
            'token_type' => 'Bearer'
        ];
    }

    private function createToken(array $userData, int $expiry, string $type = 'access'): string {
        // Ensure we have the required data
        if (!isset($userData['sub']) || !isset($userData['email'])) {
            throw new \InvalidArgumentException('Invalid user data for token creation');
        }

        $payload = [
            'sub' => $userData['sub'],
            'email' => $userData['email'],
            'level' => $userData['level'],
            'type' => $type,
            'iat' => time(),
            'exp' => time() + $expiry
        ];

        return JWT::encode($payload, $this->key, 'HS256');
    }

    public function validateToken(string $token, string $expectedType = 'access'): ?array {
        // Check if token is blacklisted
        if ($this->cache->get("token_blacklist:{$token}")) {
            return null;
        }

        try {
            // Decode and validate token
            $payload = (array) JWT::decode($token, new Key($this->key, 'HS256'));

            // Verify token type
            if (!isset($payload['type']) || $payload['type'] !== $expectedType) {
                return null;
            }

            // For refresh tokens, verify if it's still valid in cache
            if ($expectedType === 'refresh' && isset($payload['sub']) && isset($payload['jti'])) {
                $storedHash = $this->cache->get("refresh_token:{$payload['sub']}:{$payload['jti']}");
                if (!$storedHash || !hash_equals($storedHash, hash('sha256', $token))) {
                    return null;
                }
            }

            return $payload;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function blacklistToken(string $token): void {
        $payload = $this->validateToken($token);
        if ($payload) {
            // Blacklist the token
            $this->cache->put(
                "token_blacklist:{$token}",
                true,
                $payload['exp'] - time()
            );

            // If it's a refresh token, invalidate it in the rotation tracking
            if (
                isset($payload['type']) && $payload['type'] === 'refresh' &&
                isset($payload['sub']) && isset($payload['jti'])
            ) {
                $this->cache->delete("refresh_token:{$payload['sub']}:{$payload['jti']}");
            }
        }
    }

    public function rotateRefreshToken(string $oldRefreshToken, array $userData): ?array {
        // Validate old refresh token
        $payload = $this->validateToken($oldRefreshToken, 'refresh');
        if (!$payload) {
            return null;
        }

        // Blacklist old refresh token
        $this->blacklistToken($oldRefreshToken);

        // Create new token pair
        return $this->createTokenPair($userData);
    }
}