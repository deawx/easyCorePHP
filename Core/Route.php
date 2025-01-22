<?php

namespace Core;

use Closure;

/*
    คลาส Route สำหรับจัดการการกำหนดเส้นทางในแอปพลิเคชัน
*/

class Route {
    // อาร์เรย์เก็บรายการเส้นทางทั้งหมด
    /** @var array<Core> */
    private static array $routes = [];

    // คำนำหน้าที่จะเพิ่มก่อนเส้นทางทั้งหมด
    private static string $prefix = '';

    private static $middleware = null;

    /**
     * สร้างรายการเส้นทางใหม่ด้วยวิธี GET
     * @param string $path - เส้นทาง
     * @param string $controller - คอนโทรลเลอร์
     * @param string|null $prefix - คำนำหน้า (ถ้ามี)
     * @return void
     */

    /**
     * @param string $path
     * @param string|Closure|null $controller
     * @param string|null $prefix
     */
    public static function get(string $path, $controller, ?string $prefix = null): void {
        if (is_string($controller) || $controller instanceof Closure) {
            self::$routes[] = new Core('GET', self::prefixPath($path, $prefix), $controller);
        } else {
            throw new \InvalidArgumentException('Argument #2 ($controller) must be of type string or Closure');
        }
    }

    /**
     * สร้างรายการเส้นทางใหม่ด้วยวิธี POST
     * @param string $path - เส้นทาง
     * @param string $controller - คอนโทรลเลอร์
     * @param string|null $prefix - คำนำหน้า (ถ้ามี)
     * @return void
     */
    public static function post(string $path, string $controller, ?string $prefix = null): void {
        self::$routes[] = new Core('POST', self::prefixPath($path, $prefix), $controller);
    }

    /**
     * สร้างรายการเส้นทางใหม่ด้วยวิธี PUT
     * @param string $path - เส้นทาง
     * @param string $controller - คอนโทรลเลอร์
     * @param string|null $prefix - คำนำหน้า (ถ้ามี)
     * @return void
     */
    public static function put(string $path, string $controller, ?string $prefix = null): void {
        self::$routes[] = new Core('PUT', self::prefixPath($path, $prefix), $controller);
    }

    /**
     * สร้างรายการเส้นทางใหม่ด้วยวิธี DELETE
     * @param string $path - เส้นทาง
     * @param string $controller - คอนโทรลเลอร์
     * @param string|null $prefix - คำนำหน้า (ถ้ามี)
     * @return void
     */
    public static function delete(string $path, string $controller, ?string $prefix = null): void {
        self::$routes[] = new Core('DELETE', self::prefixPath($path, $prefix), $controller);
    }

    /**
     * สร้างรายการเส้นทางใหม่ด้วยวิธีใดก็ได้
     * @param string $path - เส้นทาง
     * @param string $controller - คอนโทรลเลอร์
     * @param string|null $prefix - คำนำหน้า (ถ้ามี)
     * @return void
     */
    public static function any(string $path, string $controller, ?string $prefix = null): void {
        self::$routes[] = new Core('ANY', self::prefixPath($path, $prefix), $controller);
    }

    /**
     * สร้างรายการเส้นทางใหม่ด้วยวิธี OPTIONS
     * @param string $path - เส้นทาง
     * @param string $controller - คอนโทรลเลอร์
     * @param string|null $prefix - คำนำหน้า (ถ้ามี)
     * @return void
     */
    public static function options(string $path, string $controller, ?string $prefix = null): void {
        self::$routes[] = new Core('OPTIONS', self::prefixPath($path, $prefix), $controller);
    }

    /**
     * สร้างรายการเส้นทางใหม่ด้วยวิธี PATCH
     * @param string $path - เส้นทาง
     * @param string $controller - คอนโทรลเลอร์
     * @param string|null $prefix - คำนำหน้า (ถ้ามี)
     * @return void
     */
    public static function patch(string $path, string $controller, ?string $prefix = null): void {
        self::$routes[] = new Core('PATCH', self::prefixPath($path, $prefix), $controller);
    }

    /**
     * ตั้งค่าคำนำหน้าสำหรับเส้นทางทั้งหมดภายในฟังก์ชัน callback
     * @param string $prefix - คำนำหน้า
     * @param array|callable $options - อาจจะเป็น callback หรือ options array
     * @return void
     */
    public static function group(string $prefix, $options): void {
        $previousPrefix = self::$prefix;
        $previousMiddleware = self::$middleware;

        self::setPrefix(self::prefixPath($prefix));

        if (is_array($options) && isset($options['middleware'])) {
            self::$middleware = $options['middleware'];
            if (isset($options[0]) && is_callable($options[0])) {
                $options[0]();
            }
        } else if (is_callable($options)) {
            $options();
        }

        self::setPrefix($previousPrefix);
        self::$middleware = $previousMiddleware;
    }

    /**
     * รันการจับคู่เส้นทางและดำเนินการคอนโทรลเลอร์ที่ตรงกัน
     * @return void
     */
    public static function run(): void {
        $request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $request_uri = $_SERVER['REQUEST_URI'] ?? '/';

        $matchedRoute = null;

        foreach (self::getRoutes() as $route) {
            if ($route->match($request_method, $request_uri)) {
                if (!empty($route->getParams())) {
                    $matchedRoute = $route;
                    continue;
                }
                $route->execute();
                return;
            }
        }

        if ($matchedRoute !== null) {
            $matchedRoute->execute();
            return;
        }

        http_response_code(404);
        include_once(__DIR__ . "/../routes/errors/404.php");
        exit();
    }

    /**
     * ดึงค่า routes
     * @return array<Core> - อาร์เรย์ของ routes
     */
    public static function getRoutes(): array {
        return self::$routes;
    }

    /**
     * ตั้งค่า routes
     * @param array<Core> $routes - อาร์เรย์ของ routes
     * @return void
     */
    public static function setRoutes(array $routes): void {
        self::$routes = $routes;
    }

    /**
     * ตั้งค่าคำนำหน้า
     * @param string $prefix - คำนำหน้า
     * @return void
     */
    public static function setPrefix(string $prefix): void {
        self::$prefix = rtrim($prefix, '/') . '/';
    }

    /**
     * ดึงค่าคำนำหน้า
     * @return string - คำนำหน้า
     */
    public static function getPrefix(): string {
        return self::$prefix;
    }

    /**
     * ฟังก์ชันช่วยเหลือในการเพิ่มคำนำหน้าก่อนเส้นทาง
     * @param string $path - เส้นทาง
     * @param string|null $prefix - คำนำหน้า (ถ้ามี)
     * @return string - เส้นทางที่มีคำนำหน้า
     */
    public static function prefixPath(string $path, ?string $prefix = null): string {
        if (empty($prefix)) {
            $prefix = self::$prefix;
        }

        if ($path === '/') {
            return rtrim($prefix, '/');
        }

        return rtrim($prefix, '/') . '/' . ltrim($path, '/');
    }
}