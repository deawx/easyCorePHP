<?php

namespace Core;

class Security {
    private static array $defaultCSPDirectives = [
        'default-src' => "'self'",
        'script-src' => "'self' 'unsafe-inline' 'unsafe-eval'",
        'style-src' => "'self' 'unsafe-inline'",
        'img-src' => "'self' data: https:",
        'font-src' => "'self'",
        'connect-src' => "'self'",
        'frame-src' => "'none'",
        'object-src' => "'none'"
    ];

    /**
     * Set security headers
     * @return void
     */
    public static function setSecurityHeaders(): void {
        // Content Security Policy
        $csp = self::buildCSPHeader();
        header("Content-Security-Policy: " . $csp);

        // Other security headers
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: DENY");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

        // Set secure cookie parameters
        $params = session_get_cookie_params();
        session_set_cookie_params([
            'lifetime' => $params['lifetime'],
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'] ?? '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    /**
     * Build CSP header from directives
     * @return string
     */
    private static function buildCSPHeader(): string {
        $policy = [];
        foreach (self::$defaultCSPDirectives as $directive => $value) {
            $policy[] = $directive . ' ' . $value;
        }
        return implode('; ', $policy);
    }

    /**
     * Configure session security
     * @return void
     */
    public static function secureSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_secure', '1');
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.gc_maxlifetime', '1440');
            session_start();
        }

        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 3600) {
            // Regenerate session ID every hour
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
    /**
     * Sanitize and validate string input
     * @param string|null $input Input to sanitize
     * @param string $type Type of validation (email, url, text, number)
     * @param int $maxLength Maximum allowed length
     * @return string|null
     */
    public static function sanitize(?string $input, string $type = 'text', int $maxLength = 255): ?string {
        if ($input === null) return null;

        // Trim whitespace
        $input = trim($input);

        // Enforce maximum length
        if (strlen($input) > $maxLength) {
            $input = substr($input, 0, $maxLength);
        }

        // Type-specific validation
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_VALIDATE_EMAIL) ?
                    filter_var($input, FILTER_SANITIZE_EMAIL) : null;

            case 'url':
                return filter_var($input, FILTER_VALIDATE_URL) ?
                    filter_var($input, FILTER_SANITIZE_URL) : null;

            case 'number':
                return filter_var($input, FILTER_VALIDATE_INT) ?
                    filter_var($input, FILTER_SANITIZE_NUMBER_INT) : null;

            case 'text':
            default:
                // Remove ASCII control characters
                $input = preg_replace('/[\x00-\x1F\x7F]/u', '', $input);
                return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }

    /**
     * Sanitize and validate array input recursively
     * @param array<mixed> $input Array to sanitize
     * @param array<string,string> $rules Validation rules for specific keys
     * @return array<mixed>
     */
    public static function sanitizeArray(array $input, array $rules = []): array {
        $result = [];
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $result[$key] = self::sanitizeArray($value, $rules);
            } else if (is_string($value)) {
                $type = $rules[$key] ?? 'text';
                $result[$key] = self::sanitize($value, $type);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Sanitize SQL query parameters
     * @param mixed $value Value to sanitize
     * @param \PDO $pdo PDO connection
     * @return string
     */
    public static function sanitizeSQL(mixed $value, \PDO $pdo): string {
        if (is_null($value)) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }
        return $pdo->quote((string)$value);
    }

    /**
     * Generate CSRF token with expiration
     * @param int $expiry Token expiry time in seconds
     * @return string
     */
    public static function generateCsrfToken(int $expiry = 3600): string {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = [
            'value' => $token,
            'expiry' => time() + $expiry
        ];
        return $token;
    }

    /**
     * Verify CSRF token
     * @param string $token Token to verify
     * @return bool
     */
    public static function verifyCsrfToken(string $token): bool {
        if (
            !isset($_SESSION['csrf_token']) ||
            !isset($_SESSION['csrf_token']['value']) ||
            !isset($_SESSION['csrf_token']['expiry'])
        ) {
            return false;
        }

        // Check expiration
        if (time() > $_SESSION['csrf_token']['expiry']) {
            unset($_SESSION['csrf_token']);
            return false;
        }

        // Verify token using hash_equals to prevent timing attacks
        $valid = hash_equals($_SESSION['csrf_token']['value'], $token);

        // Rotate token after verification
        if ($valid) {
            self::generateCsrfToken();
        }

        return $valid;
    }

    /**
     * Generate secure random string
     * @param int $length Length of the string
     * @param string $chars Characters to use
     * @return string
     */
    public static function generateRandomString(int $length = 32, string $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'): string {
        $bytes = random_bytes($length);
        $result = '';
        $max = mb_strlen($chars) - 1;

        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[ord($bytes[$i]) % ($max + 1)];
        }

        return $result;
    }
}