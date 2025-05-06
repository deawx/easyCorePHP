<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\User;
use Core\Auth\TokenManager;

class MemberController extends Controller {
    private TokenManager $tokenManager;

    public function __construct() {
        $this->tokenManager = new TokenManager();
    }

    private function getBearerToken(): ?string {
        $headers = apache_request_headers();
        $auth = $headers['Authorization'] ?? '';

        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function profile(): never {
        $token = $this->getBearerToken();

        if (!$token) {
            $this->json([
                'status' => 'error',
                'message' => 'No authorization token found'
            ], 401);
        }

        $payload = $this->tokenManager->validateToken($token);

        if (!$payload) {
            $this->json([
                'status' => 'error',
                'message' => 'Invalid or expired token'
            ], 401);
        }

        $userModel = new User();
        $user = $userModel->find($payload['sub']);

        if (!$user) {
            $this->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }

        $this->json([
            'status' => 'success',
            'data' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'surname' => $user['surname'],
                'email' => $user['email'],
                'level' => $user['level']
            ]
        ]);
    }
}
