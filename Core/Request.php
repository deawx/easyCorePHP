<?php

namespace Core;

class Request {
    private array $get;
    private array $post;
    private array $server;
    private array $files;
    private array $headers;

    public function __construct() {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->files = $_FILES;
        $this->headers = getallheaders();
    }

    public function get(string $key, mixed $default = null): mixed {
        return $this->get[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed {
        return $this->post[$key] ?? $default;
    }

    public function file(string $key): ?array {
        return $this->files[$key] ?? null;
    }

    public function getMethod(): string {
        return strtoupper($this->server['REQUEST_METHOD']);
    }

    public function isGet(): bool {
        return $this->getMethod() === 'GET';
    }

    public function isPost(): bool {
        return $this->getMethod() === 'POST';
    }

    public function getHeader(string $key, mixed $default = null): mixed {
        return $this->headers[$key] ?? $default;
    }
}