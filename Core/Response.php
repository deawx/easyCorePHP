<?php

namespace Core;

class Response {
    private int $statusCode = 200;
    private array $headers = [];

    public function setHeader(string $key, string $value): void {
        $this->headers[$key] = $value;
    }

    public function setStatusCode(int $code): void {
        $this->statusCode = $code;
    }

    public function json(mixed $data): never {
        $this->setHeader('Content-Type', 'application/json');
        $this->sendHeaders();
        echo json_encode($data);
        exit;
    }

    public function redirect(string $url, int $statusCode = 302): never {
        $this->setHeader('Location', $url);
        $this->setStatusCode($statusCode);
        $this->sendHeaders();
        exit;
    }

    private function sendHeaders(): void {
        http_response_code($this->statusCode);
        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }
    }
}