<?php

declare(strict_types=1);

namespace Core;

use Medoo\Medoo;
use Core\Security;

class Model {
    protected ?Medoo $db;
    protected string $table = '';  // Initialize as empty string
    protected array $fillable = [];
    protected ?int $id = null;
    protected ?string $error = null;  // Add error property

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
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
        if (empty($this->table)) {
            $this->error = 'No table name specified';
            return false;
        }

        $data = [];
        foreach ($this->fillable as $field) {
            if (isset($this->$field)) {
                $data[$field] = is_string($this->$field) ?
                    Security::sanitize($this->$field) :
                    $this->$field;
            }
        }

        if (empty($data)) {
            $this->error = 'No data to save';
            return false;
        }

        try {
            $result = $this->db->insert($this->table, $data);

            if ($this->db->error) {
                $this->error = "Database error";
                return false;
            }

            if ($result && $result->rowCount() > 0) {
                $this->id = (int)$this->db->id();
                $data['id'] = $this->id;

                foreach ($data as $key => $value) {
                    $this->$key = $value;
                }

                return true;
            }

            $this->error = 'Failed to insert record';
            return false;
        } catch (\Exception $e) {
            $this->error = 'Database error occurred';
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