<?php

namespace Core;

class Hash {
    /**
     * Create a hash of the password
     *
     * @param string $password
     * @return string
     */
    public static function make($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Verify a password against a hash
     *
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verify($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Generate a random token
     *
     * @param int $length
     * @return string
     */
    public static function makeToken($length = 16) {
        return bin2hex(random_bytes($length));
    }
}