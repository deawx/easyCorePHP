<?php

declare(strict_types=1);

namespace Core;

use Core\Request;
use Core\Response;
use Core\Session;
use Core\Security;

class Controller {
    protected Request $request;
    protected Response $response;

    public function __construct() {
        Session::start();
        $this->request = new Request();
        $this->response = new Response();
    }

    protected function view(string $view, array $data = []): void {
        // Sanitize all data before passing to view
        $sanitizedData = Security::sanitizeArray($data);

        // Add CSRF token to all views
        $sanitizedData['csrf_token'] = Security::generateCsrfToken();

        extract($sanitizedData);

        $viewPath = realpath("../app/views/{$view}.php");
        if (!$viewPath) {
            throw new \RuntimeException("View file not found: {$view}");
        }
        require_once $viewPath;
    }

    protected function redirect(string $url): never {
        $url = Security::sanitize($url);
        header("Location: {$url}");
        exit;
    }

    protected function json(mixed $data, int $statusCode = 200): never {
        header('Content-Type: application/json');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        http_response_code($statusCode);
        echo json_encode(Security::sanitizeArray((array)$data));
        exit;
    }

    protected function validateCsrf(): bool {
        $token = $this->request->post('csrf_token');
        return $token && Security::verifyCsrfToken($token);
    }
}