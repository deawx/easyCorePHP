<?php

namespace Core;

use PDO;
use PDOException;
use Medoo\Medoo;

class Database {
    private static ?Database $instance = null; // เก็บ instance ของคลาสนี้
    private Medoo $connection; // เก็บการเชื่อมต่อฐานข้อมูล

    // Constructor ของคลาสทำงานเพียงครั้งเดียวเมื่อสร้าง instance
    private function __construct() {
        try {
            // ตรวจสอบว่า environment variables ที่จำเป็นถูกตั้งค่า
            if (!isset($_ENV['DB_TYPE'], $_ENV['DB_HOST'], $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD'])) {
                throw new \Exception('Database configuration is missing.');
            }

            // สร้างการเชื่อมต่อฐานข้อมูลด้วย Medoo
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
        } catch (PDOException $e) {
            // โยนข้อผิดพลาดในกรณีที่การเชื่อมต่อล้มเหลว
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        } catch (\Exception $e) {
            // จัดการข้อผิดพลาดที่ไม่ใช่ PDOException
            die('An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * ฟังก์ชันสำหรับคืนค่า instance ของ Database
     * @return Database
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * ฟังก์ชันสำหรับเชื่อมต่อฐานข้อมูล
     * @return void
     */
    public static function connect(): void {
        self::getInstance();
    }

    /**
     * ฟังก์ชันสำหรับคืนค่าการเชื่อมต่อฐานข้อมูล
     * @return Medoo
     */
    public function getConnection(): Medoo {
        return $this->connection;
    }
}
