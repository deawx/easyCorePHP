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

    public function __construct() {
        // สร้าง default secret key ที่ซับซ้อนและยาว 64 ตัวอักษร
        $defaultKey = 'hK8cX#mP2$vN9qL5wR7tY4jF6nB3gV8hD1sA4mE9xZ2pQ7yU5wC8';

        // ใช้ค่าจาก ENV ถ้ามี ถ้าไม่มีใช้ค่า default
        $this->key = $_ENV['JWT_SECRET'] ?? $defaultKey;
        $this->accessTokenExpiry = 3600;      // 1 ชั่วโมง (60 นาที)
        $this->refreshTokenExpiry = 604800;   // 1 สัปดาห์ (7 วัน)
        $this->cache = new Cache();
    }

    public function createTokenPair(array $userData): array {
        // Validate required data
        if (!isset($userData['sub']) || !isset($userData['email'])) {
            throw new \InvalidArgumentException('User data must contain sub and email');
        }

        $accessToken = $this->createToken($userData, $this->accessTokenExpiry);
        $refreshToken = $this->createToken($userData, $this->refreshTokenExpiry, 'refresh');

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $this->accessTokenExpiry
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

    public function validateToken(string $token): ?array {
        // ตรวจสอบว่า token อยู่ใน blacklist หรือไม่
        if ($this->cache->get("token_blacklist:{$token}")) {
            return null;
        }

        try {
            return (array) JWT::decode($token, new Key($this->key, 'HS256'));
        } catch (\Exception $e) {
            return null;
        }
    }

    public function blacklistToken(string $token): void {
        $payload = $this->validateToken($token);
        if ($payload) {
            $this->cache->put(
                "token_blacklist:{$token}",
                true,
                $payload['exp'] - time()
            );
        }
    }
}