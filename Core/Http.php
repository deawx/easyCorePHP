<?php

namespace Core;

/**
 * Http - คลาส PHP ที่ง่ายสำหรับการทำ HTTP requests ด้วย cURL
 */
class Http {
    private \CurlHandle $curlhandle;
    private array $options = [];
    private array $responseHeaders = [];
    private string $responseBody;

    /**
     * Constructor - สร้าง handle ของ cURL ใหม่และตั้งค่า default options
     */
    public function __construct() {
        $this->curlhandle = curl_init();
        $this->setDefaults();
    }

    /**
     * Headers - กำหนด HTTP headers สำหรับคำขอ
     *
     * @param array<int, string> $headers - array ของ headers ที่จะกำหนด
     * @return Http - คืนค่า Http object สำหรับการเชื่อมโยงเมธอด
     */
    public function Headers(array $headers): self {
        $this->options[CURLOPT_HTTPHEADER] = $headers;
        return $this;
    }

    /**
     * Option - กำหนด option ของ cURL สำหรับคำขอ
     *
     * @param int $option - option ที่จะกำหนด
     * @param mixed $value - ค่าที่จะตั้งค่าสำหรับ option นั้น
     * @return Http - คืนค่า Http object สำหรับการเชื่อมโยงเมธอด
     */
    public function Option(int $option, $value): self {
        curl_setopt($this->curlhandle, $option, $value);
        return $this;
    }

    /**
     * Timeout - กำหนดระยะเวลาหมดเวลาของคำขอในหน่วยวินาที
     *
     * @param int $timeout - ระยะเวลาหมดเวลาในหน่วยวินาที
     * @return Http - คืนค่า Http object สำหรับการเชื่อมโยงเมธอด
     */
    public function Timeout(int $timeout): self {
        $this->Option(CURLOPT_TIMEOUT, $timeout);
        return $this;
    }

    /**
     * Url - กำหนด URL สำหรับคำขอ
     *
     * @param string $url - URL ที่จะตั้งค่า
     * @return Http - คืนค่า Http object สำหรับการเชื่อมโยงเมธอด
     */
    public function Url(string $url): self {
        $this->Option(CURLOPT_URL, $url);
        return $this;
    }

    /**
     * Method - กำหนด HTTP method สำหรับคำขอ
     *
     * @param string $method - HTTP method ที่จะตั้งค่า (เช่น "GET", "POST", เป็นต้น)
     * @return Http - คืนค่า Http object สำหรับการเชื่อมโยงเมธอด
     */
    public function Method(string $method): self {
        $this->Option(CURLOPT_CUSTOMREQUEST, strtoupper($method));
        return $this;
    }

    /**
     * Body - กำหนด request body สำหรับคำขอ
     *
     * @param mixed $body - request body ที่จะตั้งค่า
     * @return Http - คืนค่า Http object สำหรับการเชื่อมโยงเมธอด
     */
    public function Body($body): self {
        $this->Option(CURLOPT_POSTFIELDS, $body);
        return $this;
    }

    /**
     * getStatus - คืนค่า HTTP status code ของ response
     *
     * @return int - HTTP status code
     */
    public function getStatus(): int {
        return curl_getinfo($this->curlhandle, CURLINFO_HTTP_CODE);
    }

    /**
     * Encoding - กำหนด encoding(s) สำหรับคำขอ
     *
     * @param string|array<int, string> $encodings - encoding(s) ที่จะตั้งค่า
     * @return Http - คืนค่า Http object สำหรับการเชื่อมโยงเมธอด
     */
    public function Encoding($encodings): self {
        if (is_array($encodings)) {
            $encodings = implode(',', $encodings);
        }
        $this->Option(CURLOPT_ENCODING, $encodings);
        return $this;
    }

    /**
     * MaxRedirects - กำหนดจำนวนครั้งสูงสุดของการ redirect ที่จะติดตาม
     *
     * @param int $maxRedirects - จำนวนครั้งสูงสุดของการ redirect
     * @return Http - คืนค่า Http object สำหรับการเชื่อมโยงเมธอด
     */
    public function MaxRedirects(int $maxRedirects): self {
        $this->Option(CURLOPT_MAXREDIRS, $maxRedirects);
        return $this;
    }

    /**
     * VerifyPeer - กำหนดว่าจะตรวจสอบ SSL certificate ของ peer หรือไม่
     *
     * @param bool $verify - กำหนดว่าจะตรวจสอบ SSL certificate ของ peer หรือไม่
     * @return Http - คืนค่า Http object สำหรับการเชื่อมโยงเมธอด
     */
    public function VerifyPeer(bool $verify): self {
        $this->Option(CURLOPT_SSL_VERIFYPEER, $verify);
        return $this;
    }

    /**
     * Proxy - กำหนด proxy สำหรับคำขอ
     *
     * @param string $proxy - proxy ที่จะตั้งค่า
     * @return Http - คืนค่า Http object สำหรับการเชื่อมโยงเมธอด
     */
    public function Proxy(string $proxy): self {
        $this->Option(CURLOPT_PROXY, $proxy);
        return $this;
    }

    /**
     * Send - ส่ง HTTP request และคืนค่าการตอบกลับ
     *
     * @return string - การตอบกลับจากเซิร์ฟเวอร์
     * @throws \Exception - ถ้า cURL พบข้อผิดพลาดขณะทำคำขอ
     */
    public function Send(): string {
        curl_setopt_array($this->curlhandle, $this->options);
        $response = curl_exec($this->curlhandle);
        if ($response === false) {
            throw new \Exception(curl_error($this->curlhandle), curl_errno($this->curlhandle));
        }

        // แยกการตอบกลับออกเป็น headers และ body
        $headerSize = curl_getinfo($this->curlhandle, CURLINFO_HEADER_SIZE);
        $this->responseHeaders = substr($response, 0, $headerSize);
        $this->responseBody = substr($response, $headerSize);

        return $this->responseBody;
    }

    /**
     * getHeaders - คืนค่า response headers
     *
     * @return array<string, string> - response headers
     */
    public function getHeaders(): array {
        if (empty($this->responseHeaders)) {
            $this->Send();
        }
        return $this->responseHeaders;
    }

    /**
     * getBody - คืนค่า response body
     *
     * @return string - response body
     * @throws \Exception - ถ้า cURL พบข้อผิดพลาดขณะทำคำขอ
     */
    public function getBody(): string {
        if (empty($this->responseBody)) {
            $this->Send();
        }
        return $this->responseBody;
    }

    /**
     * setDefaults - ตั้งค่า default options ของ cURL สำหรับคำขอ
     *
     * @return void
     */
    private function setDefaults(): void {
        $defaults = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYPEER => true, // ตรวจสอบ SSL certificate สำหรับการผลิต
            CURLOPT_ENCODING => '',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        ];
        curl_setopt_array($this->curlhandle, $defaults);
    }

    /**
     * reset - รีเซ็ตการตั้งค่า cURL handle
     *
     * @return void
     */
    public function reset(): void {
        curl_reset($this->curlhandle);
        $this->setDefaults();
    }

    /**
     * Destructor - ปิด cURL handle เมื่อ object ถูกทำลาย
     */
    public function __destruct() {
        curl_close($this->curlhandle);
    }
}
