<?php

namespace Core;

class Json {
    /**
     * clean - ทำความสะอาดและแสดงข้อมูลเป็น JSON พร้อมการจัดรูปแบบและการเข้ารหัส Unicode และ slashes
     *
     * @param array<string, mixed> $data - ข้อมูลที่จะถูกแสดงผลเป็น JSON
     * @param int $code - รหัสสถานะ HTTP (ค่าเริ่มต้นคือ 200)
     * @return void
     */
    public static function clean(array $data, int $code = 200): void {
        http_response_code($code); // ตั้งค่า HTTP Code
        header('Content-type: application/json; charset=utf-8'); // ตั้งค่า header สำหรับประเภทเนื้อหาที่เป็น JSON
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); // เข้ารหัสข้อมูลเป็น JSON พร้อมการจัดรูปแบบและการเข้ารหัส Unicode และ slashes
        if ($json === false) { // ตรวจสอบว่าการเข้ารหัส JSON ล้มเหลวหรือไม่
            $jsonError = json_last_error_msg(); // ดึงข้อความข้อผิดพลาดจาก JSON encoding
            http_response_code(500); // ตั้งค่า HTTP Code เป็น 500 ในกรณีที่เกิดข้อผิดพลาด
            $json = json_encode(['error' => 'JSON encoding failed', 'message' => $jsonError]);
        }
        echo $json; // แสดงผล JSON
        exit; // หยุดการทำงานเพิ่มเติม
    }

    /**
     * show - แสดงข้อมูลเป็น JSON โดยไม่มีการจัดรูปแบบพิเศษ
     *
     * @param array<string, mixed> $data - ข้อมูลที่จะถูกแสดงผลเป็น JSON
     * @param int $code - รหัสสถานะ HTTP (ค่าเริ่มต้นคือ 200)
     * @return void
     */
    public static function show(array $data, int $code = 200): void {
        http_response_code($code); // ตั้งค่า HTTP Code
        header('Content-type: application/json; charset=utf-8'); // ตั้งค่า header สำหรับประเภทเนื้อหาที่เป็น JSON
        $json = json_encode($data); // เข้ารหัสข้อมูลเป็น JSON
        if ($json === false) { // ตรวจสอบว่าการเข้ารหัส JSON ล้มเหลวหรือไม่
            $jsonError = json_last_error_msg(); // ดึงข้อความข้อผิดพลาดจาก JSON encoding
            http_response_code(500); // ตั้งค่า HTTP Code เป็น 500 ในกรณีที่เกิดข้อผิดพลาด
            $json = json_encode(['error' => 'JSON encoding failed', 'message' => $jsonError]);
        }
        echo $json; // แสดงผล JSON
        exit; // หยุดการทำงานเพิ่มเติม
    }
}
