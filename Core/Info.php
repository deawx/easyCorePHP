<?php

namespace Core;

/*
    คลาส Info สำหรับดึงข้อมูลต่าง ๆ เกี่ยวกับผู้ใช้และเซิร์ฟเวอร์
*/

class Info {

    /*
        ดึงชื่อโดเมนของเซิร์ฟเวอร์
        @return string ชื่อโดเมนของเซิร์ฟเวอร์
     */
    public static function Domain(): string {
        return $_SERVER['SERVER_NAME'] ?? 'Unknown';
    }

    /*
        ดึงชื่อโฮสต์ที่ใช้ใน HTTP request
        @return string ชื่อโฮสต์ที่ใช้ใน HTTP request
     */
    public static function Host(): string {
        return $_SERVER['HTTP_HOST'] ?? 'Unknown';
    }

    /*
        ดึงที่อยู่ IP ของโฮสต์ของเซิร์ฟเวอร์
        @return string ที่อยู่ IP ของโฮสต์ของเซิร์ฟเวอร์
     */
    public static function HostIP(): string {
        return gethostbyname($_SERVER['SERVER_NAME'] ?? 'Unknown');
    }

    /*
        ดึงที่อยู่ IP ของผู้ใช้ที่ทำคำขอ
        @return string ที่อยู่ IP ของผู้ใช้ที่ทำคำขอ
     */
    public static function UserIP(): string {
        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }

    /*
        ดึงคุกกี้ที่ส่งโดยผู้ใช้
        @return array<string, string> แอสโซซิเอทีฟอาร์เรย์ของคุกกี้ที่ส่งโดยผู้ใช้
     */
    public static function Cookies(): array {
        /** @var array<string, string> $cookies */
        $cookies = $_COOKIE;
        return $cookies;
    }

    /*
        ดึงสตริงคุกกี้ทั้งหมดที่ส่งโดยผู้ใช้
        @return string สตริงคุกกี้ทั้งหมดที่ส่งโดยผู้ใช้
     */
    public static function FullCookie(): string {
        return $_SERVER['HTTP_COOKIE'] ?? 'Unknown';
    }

    /*
        ดึง path ของคำขอปัจจุบัน
        @return string path ของคำขอปัจจุบัน
     */
    public static function Path(): string {
        return $_SERVER['REQUEST_URI'] ?? 'Unknown';
    }

    /*
        ดึงประเภทอุปกรณ์ที่ผู้ใช้ใช้งาน
        @return string ประเภทอุปกรณ์ที่ผู้ใช้ใช้งาน (Mobile, Tablet, หรือ Desktop)
     */
    public static function Device(): string {
        $userAgent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
        if (strpos($userAgent, 'mobile') !== false) {
            return 'Mobile';
        } elseif (strpos($userAgent, 'tablet') !== false) {
            return 'Tablet';
        } else {
            return 'Desktop';
        }
    }

    /*
        ดึงสตริง user agent ที่ส่งโดยผู้ใช้
        @return string สตริง user agent ที่ส่งโดยผู้ใช้
     */
    public static function UserAgent(): string {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }

    /*
        ดึง HTTP headers ที่ส่งโดยผู้ใช้
        @return array<string, string> แอสโซซิเอทีฟอาร์เรย์ของ HTTP headers ที่ส่งโดยผู้ใช้
     */
    public static function getHeaders(): array {
        /** @var array<string, string> $headers */
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (strpos($name, 'HTTP') === 0) {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }

    /*
        แสดงคุกกี้ที่ส่งโดยผู้ใช้
        @return void
     */
    public static function echoCookies(): void {
        foreach ($_COOKIE as $key => $value) {
            echo $key . ': ' . $value . '<br>';
        }
    }

    /*
        ดึงระบบปฏิบัติการของผู้ใช้
        @return string ระบบปฏิบัติการของผู้ใช้
     */
    public static function getOS(): string {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $devices = [
            'Windows Phone' => 'Windows Phone',
            'iPhone' => 'iPhone',
            'iPad' => 'iPad',
            'Kindle' => 'Silk',
            'Android' => 'Android',
            'PlayBook' => 'PlayBook',
            'BlackBerry' => 'BlackBerry',
            'Macintosh' => 'Macintosh',
            'Linux' => 'Linux',
            'Windows' => 'Windows'
        ];

        foreach ($devices as $os => $device) {
            if (strpos($userAgent, $device) !== false) {
                return $os;
            }
        }

        return 'Unknown';
    }
}
