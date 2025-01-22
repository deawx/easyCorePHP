<?php

namespace Core;

class Cipher {
    private string $key;      // กุญแจสำหรับการเข้ารหัสและถอดรหัส
    private string $cipher;   // ชื่อของอัลกอริธึมการเข้ารหัส

    public function __construct(string $key = null, string $cipher = 'AES-256-CBC') {
        if ($key === null) {
            $key = self::generateKey(); // สร้างกุญแจใหม่ถ้ายังไม่ได้ระบุ
        }
        $this->validateKeyLength($key, $cipher);
        $this->key = $key;
        $this->cipher = $cipher;
    }

    /**
     * สร้างกุญแจสำหรับการเข้ารหัส
     * @return string กุญแจที่ถูกสร้าง
     */
    public static function generateKey(): string {
        return bin2hex(random_bytes(16)); // สร้างกุญแจขนาด 256 บิต (32 bytes)
    }

    /**
     * ตรวจสอบความยาวของกุญแจ
     * @param string $key กุญแจ
     * @param string $cipher ชื่อของอัลกอริธึมการเข้ารหัส
     * @throws \Exception ถ้าความยาวของกุญแจไม่ถูกต้อง
     */
    private function validateKeyLength(string $key, string $cipher): void {
        $keyLength = strlen($key);
        $requiredKeyLength = 32; // AES-256 requires a 256-bit key, which is 32 bytes

        if ($keyLength !== $requiredKeyLength) {
            throw new \Exception('Key length must be ' . $requiredKeyLength . ' bytes for cipher ' . $cipher);
        }
    }

    /**
     * เข้ารหัสข้อมูล
     * @param string $data ข้อมูลที่จะเข้ารหัส
     * @return string ข้อมูลที่ถูกเข้ารหัสและแปลงเป็น base64
     * @throws \Exception ถ้าการเข้ารหัสล้มเหลว
     */
    public function encrypt(string $data): string {
        if (empty($data)) {
            throw new \Exception('Data to be encrypted must not be empty');
        }

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->cipher));
        $encrypted = openssl_encrypt($data, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);
        if ($encrypted === false) {
            throw new \Exception('Encryption failed');
        }

        // เพิ่ม HMAC เพื่อความปลอดภัย
        $hmac = hash_hmac('sha256', $encrypted, $this->key, true);

        return base64_encode($iv . $hmac . $encrypted);
    }

    /**
     * ถอดรหัสข้อมูล
     * @param string $encryptedData ข้อมูลที่ถูกเข้ารหัสและแปลงเป็น base64
     * @return string ข้อมูลที่ถูกถอดรหัส
     * @throws \Exception ถ้าการถอดรหัสล้มเหลว
     */
    public function decrypt(string $encryptedData): string {
        if (empty($encryptedData)) {
            throw new \Exception('Data to be decrypted must not be empty');
        }

        $encryptedData = base64_decode($encryptedData);
        $ivLength = openssl_cipher_iv_length($this->cipher);

        $iv = substr($encryptedData, 0, $ivLength);
        $hmac = substr($encryptedData, $ivLength, 32); // HMAC is 32 bytes
        $encrypted = substr($encryptedData, $ivLength + 32);

        // ตรวจสอบ HMAC เพื่อความปลอดภัย
        $calculatedHmac = hash_hmac('sha256', $encrypted, $this->key, true);
        if (!hash_equals($hmac, $calculatedHmac)) {
            throw new \Exception('HMAC validation failed');
        }

        $decrypted = openssl_decrypt($encrypted, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);
        if ($decrypted === false) {
            throw new \Exception('Decryption failed');
        }
        return $decrypted;
    }
}