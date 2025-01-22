<?php

namespace Core;

// ทดสอบใช้ whoops
use Whoops\Run;
use Whoops\Handler\PrettyPageHandler;
use Core\Cors;

class Core {
    private $method; // HTTP method ของ route
    private $path; // เส้นทางที่เกี่ยวข้องกับ route นี้
    private $controller; // เมธอดของคอนโทรลเลอร์ที่จะถูกเรียกใช้เมื่อ route นี้ถูกจับคู่
    private $params; // พารามิเตอร์ที่ถูกดึงออกจาก request URI เมื่อ route นี้ถูกจับคู่
    private static $pathRegexCache = []; // แคช regular expression สำหรับจับคู่ URI

    // ทดสอบใช้ whoops
    private $whoops;

    public function __construct(string $method, string $path, $controller) {
        $this->method = $method;
        $this->path = $path;
        $this->controller = $controller;

        // Setup CORS for API routes
        // Routes ที่ขึ้นต้นด้วย /api/ จะมีการจัดการ CORS โดยอัตโนมัติ
        if (str_starts_with($path, '/api/')) {
            Cors::origin(['*'])
                ->methods(['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'])
                ->headers(['Content-Type', 'Authorization', 'X-Requested-With'])
                ->credentials(true)
                ->maxAge(3600)
                ->setHeaders();

            // Handle preflight requests
            Cors::handlePreflight();
        }

        // แคช regular expression ที่สร้างโดย preg_replace
        if (!isset(self::$pathRegexCache[$path])) {
            self::$pathRegexCache[$path] = $this->generatePathRegex($path);
        }
        // ทดสอบใช้ whoops
        $this->whoops = new Run();
        $this->whoops->pushHandler(new PrettyPageHandler());
        $this->whoops->register();
    }

    private function generatePathRegex(string $path): string {
        $path_regex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([a-zA-Z0-9_]+)', $path);
        $path_regex = str_replace('/', '\/', $path_regex);
        return '/^' . $path_regex . '(\\?.*)?$/';
    }

    // Getter และ Setter สำหรับ properties ต่างๆ
    public function getMethod(): string {
        return $this->method;
    }

    public function setMethod(string $method): void {
        $this->method = $method;
    }

    public function getPath(): string {
        return $this->path;
    }

    public function setPath(string $path): void {
        $this->path = $path;
    }

    public function getController() {
        return $this->controller;
    }

    public function setController($controller): void {
        $this->controller = $controller;
    }

    public function getParams(): array {
        return $this->params;
    }

    public function setParams(array $params): void {
        $this->params = $params;
    }

    // จับคู่ route กับ request method และ URI
    public function match(string $request_method, string $request_uri): bool {
        $parsed_url = parse_url($request_uri);
        $request_path = rtrim($parsed_url['path'], '/');

        if ($this->method === $request_method && preg_match(self::$pathRegexCache[$this->path], $request_path, $matches)) {
            $this->params = array_slice($matches, 1);
            return true;
        }

        return false;
    }

    // ดำเนินการเมธอดของคอนโทรลเลอร์ที่เกี่ยวข้องกับ route นี้
    public function execute(): void {
        try {
            if (is_callable($this->controller)) {
                // ถ้า controller เป็น closure, ดำเนินการโดยตรง
                call_user_func_array($this->controller, $this->params);
            } else {
                // ถ้า controller เป็นสตริง, แยกมันเป็นคอนโทรลเลอร์และเมธอดที่จะดำเนินการ
                $this->invokeController($this->controller, $this->params);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            $this->whoops->handleException($e);
        }
    }

    // ฟังก์ชันสำหรับการเรียกใช้คอนโทรลเลอร์
    private function invokeController(string $controllerAction, array $params): void {
        list($controller, $method) = explode('@', $controllerAction);
        $controllerClass = "App\\Controllers\\" . $controller;

        static $classExistsCache = []; // แคชค่าว่าคลาสมีอยู่หรือไม่

        if (!isset($classExistsCache[$controllerClass])) {
            $classExistsCache[$controllerClass] = class_exists($controllerClass);
        }

        if ($classExistsCache[$controllerClass]) {
            $controllerInstance = new $controllerClass;

            if (method_exists($controllerInstance, $method)) {
                call_user_func_array([$controllerInstance, $method], $params);
            } else {
                throw new \Exception("Method '$method' not found in controller '$controllerClass'");
            }
        } else {
            throw new \Exception("Controller class '$controllerClass' not found");
        }
    }
}