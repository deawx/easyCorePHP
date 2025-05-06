<?php

declare(strict_types=1);

namespace Core;

use PDO;
use PDOException;
use Medoo\Medoo;
use RuntimeException;

/**
 * คลาสจัดการการเชื่อมต่อฐานข้อมูล
 * ใช้รูปแบบ Singleton Pattern เพื่อจำกัดการสร้าง instance เพียงตัวเดียว
 */
final class Database {
    private static ?self $instance = null;
    private Medoo $connection;

    /**
     * Constructor ส่วนตัว
     *
     * @throws RuntimeException เมื่อไม่สามารถเชื่อมต่อฐานข้อมูลได้
     */
    private function __construct() {
        try {
            $this->validateConfig();
            $this->initializeConnection();
        } catch (PDOException $e) {
            throw new RuntimeException("เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage());
        }
    }

    /**
     * ตรวจสอบการตั้งค่าฐานข้อมูล
     *
     * @throws RuntimeException ถ้าไม่พบการตั้งค่าที่จำเป็น
     */
    private function validateConfig(): void {
        $required = ['DB_TYPE', 'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];
        foreach ($required as $key) {
            if (!isset($_ENV[$key])) {
                throw new RuntimeException("กรุณาตั้งค่า {$key} ในไฟล์ .env");
            }
        }
    }

    /**
     * สร้างการเชื่อมต่อฐานข้อมูล
     */
    private function initializeConnection(): void {
        $this->connection = new Medoo([
            "type" => $_ENV['DB_TYPE'],
            "host" => $_ENV['DB_HOST'],
            "database" => $_ENV['DB_NAME'],
            "username" => $_ENV['DB_USER'],
            "password" => $_ENV['DB_PASSWORD'],
            "port" => $_ENV['DB_PORT'] ?? 3306,
            "charset" => $_ENV['DB_CHARSET'] ?? 'utf8',
            "error" => PDO::ERRMODE_SILENT,
            "option" => [
                PDO::ATTR_CASE => PDO::CASE_NATURAL,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // ปิดการใช้ emulate prepares เพื่อป้องกัน SQL injection
            ],
            "command" => [
                "SET SQL_MODE=ANSI_QUOTES",
            ],
        ]);
    }

    /**
     * รับ instance ของคลาส
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * รับ connection object
     */
    public function getConnection(): Medoo {
        return $this->connection;
    }

    /**
     * เชื่อมต่อฐานข้อมูล
     */
    public static function connect(): void {
        self::getInstance();
    }

    /**
     * ป้องกันการ clone object
     */
    private function __clone() {
    }

    /**
     * ป้องกันการ unserialize
     *
     * @throws RuntimeException
     */
    public function __wakeup(): void {
        throw new RuntimeException('ไม่อนุญาตให้ unserialize object นี้');
    }
}
