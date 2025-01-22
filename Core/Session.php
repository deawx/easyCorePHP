<?php

namespace Core;

class Session {

    public static function start(): void {
        if (session_status() == PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_secure', '1');
            ini_set('session.cookie_samesite', 'Strict');
            session_start();
            // 1800 วินาที = 30 นาที
            if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
                session_unset();
                session_destroy();
                session_start();
            }
            $_SESSION['LAST_ACTIVITY'] = time();
        }
    }

    public static function put(string $key, mixed $value): void {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool {
        return isset($_SESSION[$key]);
    }

    public static function get(string $key): mixed {
        return $_SESSION[$key] ?? null;
    }

    public static function delete(string $key): void {
        if (self::has($key)) {
            unset($_SESSION[$key]);
        }
    }

    public static function regenerate(): void {
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void {
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
            $sessionName = session_name();
            if ($sessionName !== false) {
                setcookie($sessionName, '', time() - 3600, '/');
            }
        }
    }

    public static function getAll(): void {
        echo '<pre>';
        print_r($_SESSION);
        echo '</pre>';
    }
}