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

        // Setup security and CORS for routes
        if (str_starts_with($path, '/api/')) {
            // Get allowed origins from environment
            $allowedOrigins = explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? '');
            $allowedOrigins = !empty($allowedOrigins) ? $allowedOrigins : [$_SERVER['HTTP_ORIGIN'] ?? ''];

            // Setup CORS with strict policies
            Cors::origin($allowedOrigins)
                ->methods(['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'])
                ->headers([
                    'Content-Type',
                    'Authorization',
                    'X-Requested-With',
                    'X-CSRF-Token'
                ])
                ->credentials(true)
                ->maxAge(3600)
                ->setHeaders();

            // Handle preflight requests with validation
            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                Cors::handlePreflight();
                exit();
            }
        }

        // Setup security headers
        Security::setSecurityHeaders();
        Security::secureSession();

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
        // Validate path format
        if (!preg_match('/^[\/a-zA-Z0-9\-_{}]+$/', $path)) {
            throw new \InvalidArgumentException('Invalid route path format');
        }

        // More specific pattern for parameters
        $path_regex = preg_replace(
            '/\{([a-zA-Z][a-zA-Z0-9_]*)\}/',
            '(?P<$1>[a-zA-Z0-9\-_]+)',
            $path
        );

        // Escape forward slashes and add start/end markers
        $path_regex = str_replace('/', '\/', $path_regex);
        return '/^' . $path_regex . '(\/)?(\?.*)?$/';
    }

    /**
     * Validate route parameters
     * @param array $params Parameters to validate
     * @return array Validated parameters
     * @throws \InvalidArgumentException
     */
    private function validateParams(array $params): array {
        $validatedParams = [];
        foreach ($params as $param) {
            // Remove any potentially harmful characters
            $param = Security::sanitize($param);

            if ($param === null || $param === '') {
                throw new \InvalidArgumentException('Invalid route parameter');
            }

            $validatedParams[] = $param;
        }
        return $validatedParams;
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

    /**
     * Match route with request method and URI
     * @param string $request_method HTTP method
     * @param string $request_uri Request URI
     * @return bool Whether the route matches
     */
    public function match(string $request_method, string $request_uri): bool {
        // Validate request method
        if (!in_array($request_method, ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'])) {
            return false;
        }

        // Parse and sanitize URL
        $parsed_url = parse_url($request_uri);
        if ($parsed_url === false) {
            return false;
        }

        $request_path = rtrim($parsed_url['path'] ?? '', '/');

        // Check for method match and path match
        if (
            $this->method === $request_method &&
            preg_match(self::$pathRegexCache[$this->path], $request_path, $matches, PREG_UNMATCHED_AS_NULL)
        ) {

            // Extract and validate named parameters
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key) && $value !== null) {
                    $params[] = $value;
                }
            }

            try {
                $this->params = $this->validateParams($params);
                return true;
            } catch (\InvalidArgumentException $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * Execute the controller method associated with this route
     */
    public function execute(): void {
        try {
            // Add CSRF protection for non-GET requests
            if ($this->method !== 'GET' && !str_starts_with($this->path, '/api/')) {
                $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
                if (!$token || !Security::verifyCsrfToken($token)) {
                    throw new \Exception('CSRF token validation failed');
                }
            }

            if (is_callable($this->controller)) {
                $result = call_user_func_array($this->controller, $this->params);
            } else {
                $result = $this->invokeController($this->controller, $this->params);
            }

            // Handle response
            if (is_array($result) || is_object($result)) {
                header('Content-Type: application/json');
                echo json_encode($result, JSON_THROW_ON_ERROR);
            } elseif (is_string($result)) {
                echo $result;
            }
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Handle errors and exceptions
     * @param \Exception $e
     */
    private function handleError(\Exception $e): void {
        if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'production') {
            // Production error handling
            http_response_code(500);
            include __DIR__ . '/../routes/errors/500.php';
            error_log($e->getMessage() . "\n" . $e->getTraceAsString());
        } else {
            // Development error handling
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