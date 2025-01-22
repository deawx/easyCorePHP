<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Hash;
use App\Models\User;  // Changed from WebUser to User
use Core\Auth\TokenManager;
use Core\Auth\RateLimiter;

class AuthController extends Controller {
    private ?array $requestData = null;
    private TokenManager $tokenManager;
    private RateLimiter $rateLimiter;

    public function __construct() {
        $this->tokenManager = new TokenManager();
        $this->rateLimiter = new RateLimiter();
    }

    private function getRequestData($key) {
        if ($this->requestData === null) {
            $input = file_get_contents('php://input');
            $this->requestData = json_decode($input, true) ?? [];
        }
        return $this->requestData[$key] ?? ($_POST[$key] ?? null);
    }

    private function hasRequestData($key) {
        $value = $this->getRequestData($key);
        return isset($value) && !empty($value);
    }

    public function register() {
        $requiredFields = ['name', 'surname', 'email', 'password'];
        foreach ($requiredFields as $field) {
            if (!$this->hasRequestData($field)) {
                return $this->json([
                    'status' => 'error',
                    'message' => "Missing required field: {$field}"
                ], 400);
            }
        }

        $email = $this->getRequestData('email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid email format'
            ], 400);
        }

        $existingUser = User::where('email', $email);  // Changed from WebUser to User
        if ($existingUser) {
            return $this->json([
                'status' => 'error',
                'message' => 'Email already exists'
            ], 409);
        }

        try {
            $user = new User();  // Changed from WebUser to User
            $user->name = $this->getRequestData('name');
            $user->surname = $this->getRequestData('surname');
            $user->email = $email;
            $user->password = Hash::make($this->getRequestData('password'));
            $user->token = Hash::makeToken();
            $user->level = 2;
            $user->status = 1;

            if (!$user->save()) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Registration failed: ' . ($user->getError() ?? 'Unknown error')
                ], 500);
            }

            return $this->json([
                'status' => 'success',
                'message' => 'User registered successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ]
            ], 201);
        } catch (\Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return $this->json([
                'status' => 'error',
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function login(): never {
        $ip = $_SERVER['REMOTE_ADDR'];

        if ($this->rateLimiter->tooManyAttempts("login:{$ip}")) {
            $this->json([
                'status' => 'error',
                'message' => 'Too many login attempts. Please try again later.'
            ], 429);
        }

        if (!$this->hasRequestData('email') || !$this->hasRequestData('password')) {
            $this->json([
                'status' => 'error',
                'message' => 'Email and password are required'
            ], 400);
        }

        $email = $this->getRequestData('email');
        $userModel = new User();
        $user = $userModel->findForLogin($email);

        if (!$user || !Hash::verify($this->getRequestData('password'), $user['password'])) {
            $this->rateLimiter->hit("login:{$ip}");
            $this->json([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ], 401);
        }

        $this->rateLimiter->clear("login:{$ip}");

        $tokens = $this->tokenManager->createTokenPair([
            'sub' => (int)$user['id'],
            'email' => $user['email'],
            'level' => $user['level']
        ]);

        $this->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email']
                ],
                'tokens' => $tokens
            ]
        ]);
    }

    private function getBearerToken(): ?string {
        $headers = apache_request_headers();
        $auth = $headers['Authorization'] ?? '';

        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function logout(): never {
        $token = $this->getBearerToken();

        if (!$token) {
            $this->json([
                'status' => 'error',
                'message' => 'No authorization token found'
            ], 401);
        }

        // Validate token before blacklisting
        $payload = $this->tokenManager->validateToken($token);
        if (!$payload) {
            $this->json([
                'status' => 'error',
                'message' => 'Invalid or expired token'
            ], 401);
        }

        $this->tokenManager->blacklistToken($token);

        $this->json([
            'status' => 'success',
            'message' => 'Logout successful'
        ]);
    }
}