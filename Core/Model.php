<?php

declare(strict_types=1);

namespace Core;

use Medoo\Medoo;
use Core\Security;

// class Model {
abstract class Model {
    protected ?Medoo $db;
    protected string $table = '';  // Initialize as empty string
    protected array $fillable = [];
    protected ?int $id = null;
    protected ?string $error = null;  // Add error property

    // public function __construct() {
    //     $this->db = Database::getInstance()->getConnection();
    // }
    public function __construct() {
        $db = Database::getInstance()->getConnection();
        if (!$db instanceof Medoo) {
            throw new \RuntimeException('ไม่สามารถเชื่อมต่อฐานข้อมูลได้');
        }
        $this->db = $db;
    }

    /**
     * Enhanced where method to support multiple conditions
     * @param array|string $conditions Column name or array of conditions
     * @param mixed $value Value (optional, only used when $conditions is string)
     * @param string $operator Comparison operator (optional, only used when $conditions is string)
     * @return array|null
     *
     * Examples:
     * where('email', 'test@example.com')
     * where('age', 18, '>=')
     * where(['email' => 'test@example.com', 'status' => 1])
     * where(['age[>]' => 18, 'status' => 1])
     */
    public static function where(array|string $conditions, mixed $value = null, string $operator = '='): ?array {
        $instance = new static();
        $where = [];

        if (is_string($conditions)) {
            // Single condition with optional operator
            if ($operator != '=') {
                $where[$conditions . '[' . $operator . ']'] = $value;
            } else {
                $where[$conditions] = $value;
            }
        } else {
            // Multiple conditions from array
            $where = $conditions;
        }

        try {
            $result = $instance->db->get($instance->table, '*', $where);
            return $result ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get multiple records with conditions
     * @param array|string $conditions Same format as where()
     * @param mixed $value
     * @param string $operator
     * @return array
     */
    public static function whereAll(array|string $conditions, mixed $value = null, string $operator = '='): array {
        $instance = new static();
        $where = [];

        if (is_string($conditions)) {
            if ($operator != '=') {
                $where[$conditions . '[' . $operator . ']'] = $value;
            } else {
                $where[$conditions] = $value;
            }
        } else {
            $where = $conditions;
        }

        return $instance->db->select($instance->table, '*', $where) ?: [];
    }

    public function save(): bool {
        try {
            $data = [];
            foreach ($this->fillable as $field) {
                if (isset($this->$field)) {
                    $data[$field] = $this->$field;
                }
            }

            if (empty($data)) {
                $this->error = "No data to save";
                return false;
            }

            // Add timestamps
            $data['created_at'] = date('Y-m-d H:i:s');

            // Sanitize data
            $data = Security::sanitizeArray($data);

            $result = $this->db->insert($this->table, $data);

            if ($result === null) {
                $this->error = "Database insert failed";
                return false;
            }

            $this->id = (int)$this->db->id();
            return true;
        } catch (\Exception $e) {
            $this->error = "Save failed";
            return false;
        }
    }

    public function getError(): ?string {
        return $this->error;
    }

    public function update(array $data, array $where): bool {
        // Sanitize input data before updating
        $sanitizedData = Security::sanitizeArray($data);
        $result = $this->db->update($this->table, $sanitizedData, $where);
        return $result && $result->rowCount() > 0;
    }

    public function delete(array $where): bool {
        $result = $this->db->delete($this->table, $where);
        return $result && $result->rowCount() > 0;
    }

    public function find(int $id): ?array {
        return $this->db->get($this->table, '*', ['id' => $id]) ?: null;
    }

    public function all(): array {
        return $this->db->select($this->table, '*') ?: [];
    }

    public function __get(string $name): mixed {
        return isset($this->$name) ? $this->$name : null;
    }

    public function __set(string $name, mixed $value): void {
        $this->$name = $value;
    }
}
