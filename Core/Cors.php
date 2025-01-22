<?php

namespace Core;

class Cors {
    /** @var array<int, string> */
    private static array $allowedOrigins = [];
    /** @var array<int, string> */
    private static array $allowedMethods = [];
    /** @var array<int, string> */
    private static array $allowedHeaders = [];
    /** @var array<int, string> */
    private static array $exposedHeaders = [];
    private static int $maxAge = 0;
    private static bool $allowCredentials = false;

    /**
     * @param array<int, string>|string $origins
     * @return self
     */
    public static function origin($origins): self {
        self::$allowedOrigins = (array) $origins;
        return new self;
    }

    /**
     * @param array<int, string>|string $methods
     * @return self
     */
    public static function methods($methods): self {
        self::$allowedMethods = (array) $methods;
        return new self;
    }

    /**
     * @param array<int, string>|string $headers
     * @return self
     */
    public static function headers($headers): self {
        self::$allowedHeaders = (array) $headers;
        return new self;
    }

    /**
     * @param array<int, string>|string $headers
     * @return self
     */
    public static function expose($headers): self {
        self::$exposedHeaders = (array) $headers;
        return new self;
    }

    /**
     * @param int $age
     * @return self
     */
    public static function maxAge(int $age): self {
        self::$maxAge = $age;
        return new self;
    }

    /**
     * @param bool $credentials
     * @return self
     */
    public static function credentials(bool $credentials): self {
        self::$allowCredentials = $credentials;
        return new self;
    }

    /**
     * @return void
     */
    public static function setHeaders(): void {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (in_array('*', self::$allowedOrigins)) {
            header('Access-Control-Allow-Origin: *');
        } elseif (in_array($origin, self::$allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
        }

        if (self::$allowCredentials) {
            header('Access-Control-Allow-Credentials: true');
        }

        if (!empty(self::$exposedHeaders)) {
            header('Access-Control-Expose-Headers: ' . implode(', ', self::$exposedHeaders));
        }

        if (self::$maxAge > 0) {
            header("Access-Control-Max-Age: " . self::$maxAge);
        }

        if (!empty(self::$allowedMethods)) {
            header('Access-Control-Allow-Methods: ' . implode(', ', self::$allowedMethods));
        }

        if (!empty(self::$allowedHeaders)) {
            header('Access-Control-Allow-Headers: ' . implode(', ', self::$allowedHeaders));
        }
    }

    /**
     * @return void
     */
    public static function handlePreflight(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            self::setHeaders();
            header("HTTP/1.1 204 No Content");
            exit();
        }
    }
}
