<?php

declare(strict_types=1);

namespace App\Middleware;

use Core\Session;
use App\Models\User;

class AuthMiddleware {
    public function handle(): void {
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

        if (!$token) {
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => 'No authentication token provided'
            ]);
            exit;
        }

        $token = str_replace('Bearer ', '', $token);
        $user = User::where('token', $token);

        if (!$user) {
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid or expired token'
            ]);
            exit;
        }

        Session::put('user_id', $user['id']);
    }
}