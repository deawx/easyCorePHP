<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class User extends Model {
    protected string $table = 'web_user';

    // Ensure these match your database columns exactly
    protected array $fillable = [
        'id',
        'name',
        'surname',
        'email',
        'password',
        'token',
        'level',
        'status'
    ];

    public function isValidEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function findByToken(string $token): ?array {
        return $this->where('token', $token);
    }

    public function findByEmail(string $email): ?array {
        return $this->where('email', $email);
    }

    public function findForLogin(string $email): ?array {
        $user = $this->where('email', $email);

        if (!$user) {
            return null;
        }

        $requiredFields = ['id', 'name', 'email', 'password'];
        foreach ($requiredFields as $field) {
            if (!isset($user[$field])) {
                return null;
            }
        }

        return $user;
    }
}