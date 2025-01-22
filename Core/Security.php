<?php

namespace Core;

class Security {
    /**
     * Sanitize string input against XSS
     * @param string|null $input
     * @return string
     */
    public static function sanitize(?string $input): string {
        if ($input === null) return '';
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Sanitize array input recursively
     * @param array<mixed> $input
     * @return array<mixed>
     */
    public static function sanitizeArray(array $input): array {
        $result = [];
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $result[$key] = self::sanitizeArray($value);
            } else if (is_string($value)) {
                $result[$key] = self::sanitize($value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Generate CSRF token
     * @return string
     */
    public static function generateCsrfToken(): string {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token
     * @param string $token
     * @return bool
     */
    public static function verifyCsrfToken(string $token): bool {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}