<?php

namespace Core;

use Core\Database;

class Migration {
    private \Medoo\Medoo $db;
    private string $table;
    /** @var array<int, array<string, mixed>> $columns */
    private array $columns = [];
    private string $tableComment = '';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function createTable(string $table, callable $callback, string $comment = ''): void {
        $this->validateIdentifier($table);
        $this->table = $table;
        $this->tableComment = $comment;
        $callback($this);
        $this->runQuery();
        $this->reset();
    }

    public function modifyTable(string $table, callable $callback): void {
        $this->table = $table;
        $callback($this);
        $this->runQuery();
        $this->reset();
    }

    public function dropTable(string $table): void {
        try {
            // 1. ค้นหา foreign keys ที่เกี่ยวข้องทั้งหมด
            $sql = "SELECT
                tc.TABLE_NAME, tc.CONSTRAINT_NAME,
                kcu.REFERENCED_TABLE_NAME
            FROM
                information_schema.TABLE_CONSTRAINTS tc
                JOIN information_schema.KEY_COLUMN_USAGE kcu
                ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
            WHERE
                tc.CONSTRAINT_TYPE = 'FOREIGN KEY'
                AND tc.TABLE_SCHEMA = DATABASE()
                AND (tc.TABLE_NAME = ? OR kcu.REFERENCED_TABLE_NAME = ?)";

            $stmt = $this->db->pdo->prepare($sql);
            $stmt->execute([$table, $table]);
            $allForeignKeys = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // 2. ลบ foreign keys ทั้งหมด
            if (!empty($allForeignKeys)) {
                echo "\n\033[33m Found " . count($allForeignKeys) . " foreign key(s) to remove: \033[0m\n";
                foreach ($allForeignKeys as $fk) {
                    try {
                        $dropFKQuery = "ALTER TABLE `{$fk['TABLE_NAME']}` DROP FOREIGN KEY `{$fk['CONSTRAINT_NAME']}`";
                        $this->db->query($dropFKQuery);
                        echo "\033[32m ✓ Dropped FK: {$fk['CONSTRAINT_NAME']} from {$fk['TABLE_NAME']} \033[0m\n";
                    } catch (\PDOException $e) {
                        echo "\033[31m ✗ Failed to drop FK: {$fk['CONSTRAINT_NAME']} - {$e->getMessage()} \033[0m\n";
                    }
                }
            }

            // 3. ลบตาราง
            $dropTableQuery = "DROP TABLE IF EXISTS `$table`";
            $this->db->query($dropTableQuery);
            echo "\n\033[42m \033[1m SUCCESS \033[0m Dropped table: `$table` \033[0m\n\n";
        } catch (\PDOException $e) {
            echo "\n\033[41m ERROR \033[0m Failed to drop table `$table`: {$e->getMessage()} \033[0m\n\n";
            throw $e;
        }
    }

    public function renameTable(string $oldName, string $newName): void {
        $query = "RENAME TABLE `$oldName` TO `$newName`";
        try {
            $this->db->query($query);
            echo "Table `$oldName` renamed to `$newName` successfully.\n";
        } catch (\PDOException $e) {
            $this->handleError($e);
        }
    }

    public function renameColumn(string $oldName, string $newName): void {
        $query = "ALTER TABLE {$this->table} CHANGE `$oldName` `$newName`";
        try {
            $this->db->query($query);
            echo "Column `$oldName` renamed to `$newName` successfully.\n";
        } catch (\PDOException $e) {
            $this->handleError($e);
        }
    }

    public function modifyColumn(string $name, string $newType, string $comment = ''): void {
        $query = "ALTER TABLE {$this->table} MODIFY `$name` $newType" . $this->addComment($comment);
        try {
            $this->db->query($query);
            echo "Column $name modified to $newType successfully.\n";
        } catch (\PDOException $e) {
            $this->handleError($e);
        }
    }

    public function dropColumn(string $name): void {
        $query = "ALTER TABLE {$this->table} DROP COLUMN `$name`";
        try {
            $this->db->query($query);
            echo "Column `$name` dropped successfully.\n";
        } catch (\PDOException $e) {
            $this->handleError($e);
        }
    }

    public function addIndex(string $name): void {
        $query = "ALTER TABLE {$this->table} ADD INDEX ($name)";
        try {
            $this->db->query($query);
            echo "Index `$name` added successfully.\n";
        } catch (\PDOException $e) {
            $this->handleError($e);
        }
    }

    public function dropIndex(string $indexName): void {
        $query = "ALTER TABLE {$this->table} DROP INDEX `$indexName`";
        try {
            $this->db->query($query);
            echo "Index `$indexName` dropped successfully.\n";
        } catch (\PDOException $e) {
            $this->handleError($e);
        }
    }

    public function id(string $comment = ''): self {
        $this->columns[] = ['definition' => "`id` INT AUTO_INCREMENT PRIMARY KEY" . $this->addComment($comment)];
        return $this;
    }

    public function string(string $name, int $length = 255, string $comment = ''): self {
        $this->columns[] = ['definition' => "`$name` VARCHAR($length) NOT NULL" . $this->addComment($comment)];
        return $this;
    }

    public function integer(string $name, string $comment = ''): self {
        $this->columns[] = ['definition' => "`$name` INT NOT NULL" . $this->addComment($comment)];
        return $this;
    }

    public function tinyint(string $name, string $comment = ''): self {
        $this->columns[] = ['definition' => "`$name` TINYINT NOT NULL" . $this->addComment($comment)];
        return $this;
    }

    public function bigint(string $name, string $comment = ''): self {
        $this->columns[] = ['definition' => "`$name` BIGINT NOT NULL" . $this->addComment($comment)];
        return $this;
    }

    public function text(string $name, string $comment = ''): self {
        $this->columns[] = ['definition' => "`$name` TEXT NOT NULL" . $this->addComment($comment)];
        return $this;
    }

    public function boolean(string $name, string $comment = ''): self {
        $this->columns[] = ['definition' => "`$name` BOOLEAN NOT NULL" . $this->addComment($comment)];
        return $this;
    }

    public function decimal(string $name, int $precision, int $scale, string $comment = ''): self {
        $this->columns[] = ['definition' => "`$name` DECIMAL($precision, $scale) NOT NULL" . $this->addComment($comment)];
        return $this;
    }

    public function date(string $name, string $comment = ''): self {
        $this->columns[] = ['definition' => "`$name` DATE NOT NULL" . $this->addComment($comment)];
        return $this;
    }

    public function dateTime(string $name, string $comment = ''): self {
        $this->columns[] = ['definition' => "`$name` DATETIME NOT NULL" . $this->addComment($comment)];
        return $this;
    }

    public function time(string $name, string $comment = ''): self {
        $this->columns[] = ['definition' => "`$name` TIME NOT NULL" . $this->addComment($comment)];
        return $this;
    }

    public function timestamp(string $name, string $comment = ''): self {
        $this->columns[] = ['definition' => "`$name` TIMESTAMP NOT NULL" . $this->addComment($comment)];
        return $this;
    }

    public function char(string $name, int $length, string $comment = ''): self {
        $this->columns[] = ['definition' => "`$name` CHAR($length) NOT NULL" . $this->addComment($comment)];
        return $this;
    }

    public function float(string $name, int $precision, int $scale, string $comment = ''): self {
        $this->columns[] = ['definition' => "`$name` FLOAT($precision, $scale) NOT NULL" . $this->addComment($comment)];
        return $this;
    }

    public function double(string $name, int $precision, int $scale, string $comment = ''): self {
        $this->columns[] = ['definition' => "`$name` DOUBLE($precision, $scale) NOT NULL" . $this->addComment($comment)];
        return $this;
    }

    public function unique(string $name): self {
        $this->columns[] = ['definition' => "UNIQUE (`$name`)"];
        return $this;
    }

    public function index(string $name): self {
        $this->columns[] = ['definition' => "INDEX (`$name`)"];
        return $this;
    }

    public function timestamps(): self {
        $this->columns[] = ['definition' => "`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP"];
        $this->columns[] = ['definition' => "`updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"];
        return $this;
    }

    public function nullable(string $name = '', string $type = ''): self {
        if (empty($name) && empty($type)) {
            // ใช้สำหรับ Method Chaining
            $lastColumn = array_pop($this->columns);
            if ($lastColumn) {
                $definition = str_replace('NOT NULL', 'NULL', $lastColumn['definition']);
                $this->columns[] = ['definition' => $definition];
            }
        } else {
            // ใช้สำหรับสร้าง nullable column โดยตรง
            $this->columns[] = ['definition' => "`$name` $type NULL"];
        }
        return $this;
    }

    public function default(mixed $value): self {
        $lastIndex = array_key_last($this->columns);
        if ($lastIndex !== null) {
            $lastColumn = $this->columns[$lastIndex];
            $defaultValue = match (gettype($value)) {
                'boolean' => $value ? 1 : 0,
                'string' => "'$value'",
                'NULL' => 'NULL',
                default => $value
            };

            // ปรับปรุง definition ของคอลัมน์ล่าสุด
            $definition = $lastColumn['definition'];
            if (!str_contains($definition, 'DEFAULT')) {
                $definition .= " DEFAULT $defaultValue";
                $this->columns[$lastIndex]['definition'] = $definition;
            }
        }
        return $this;
    }

    public function foreign(string $column, string $references, string $on, string $onDelete = 'CASCADE', string $onUpdate = 'CASCADE'): self {
        $this->columns[] = ['definition' => "FOREIGN KEY (`$column`) REFERENCES $on(`$references`) ON DELETE $onDelete ON UPDATE $onUpdate"];
        return $this;
    }

    public function dropForeign(string $keyName): void {
        $query = "ALTER TABLE {$this->table} DROP FOREIGN KEY $keyName";
        try {
            $this->db->query($query);
            echo "Foreign key $keyName dropped successfully.\n";
        } catch (\PDOException $e) {
            $this->handleError($e);
        }
    }

    // เพิ่ม method สำหรับ ENUM type
    public function enum(string $name, array $values, string $comment = ''): self {
        $valuesStr = "'" . implode("','", $values) . "'";
        $this->columns[] = ['definition' => "`$name` ENUM($valuesStr) NOT NULL" . $this->addComment($comment)];
        return $this;
    }

    // เพิ่ม method สำหรับ JSON type
    public function json(string $name, string $comment = ''): self {
        $this->columns[] = ['definition' => "`$name` JSON" . $this->addComment($comment)];
        return $this;
    }

    // เพิ่มการตรวจสอบชื่อตารางและคอลัมน์
    private function validateIdentifier(string $name): bool {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw new \InvalidArgumentException("Invalid identifier name: $name");
        }
        return true;
    }

    private function addComment(string $comment): string {
        $comment = trim($comment);
        $comment = preg_replace('/[\n\r\t]/', '', $comment);
        return $comment ? " COMMENT '" . addslashes($comment) . "'" : '';
    }

    public function addColumn(string $columnDefinition, string $comment = ''): self {
        $this->columns[] = ['definition' => $columnDefinition . $this->addComment($comment)];
        return $this;
    }

    private function runQuery(): void {
        $columns = implode(", ", array_column($this->columns, 'definition'));
        $comment = $this->tableComment ? " COMMENT='{$this->tableComment}'" : "";
        $query = "CREATE TABLE IF NOT EXISTS {$this->table} ($columns) ENGINE=INNODB $comment;";
        // echo $query . "\n";

        try {
            $this->db->query($query);
            echo "\033[42m \033[7m\033[1m Successfully!! \033[0m \033[32m Create taable : `$this->table` \033[0m\n\n";
        } catch (\PDOException $e) {
            $this->handleError($e);
        }
    }

    private function reset(): void {
        $this->columns = [];
        $this->tableComment = '';
    }

    private function handleError(\PDOException $e): void {
        $errorCode = $e->getCode();
        $errorMessage = match ($errorCode) {
            '42S01' => 'Table already exists',
            '42S02' => 'Table not found',
            '42000' => 'Syntax error in SQL statement',
            default => $e->getMessage()
        };
        echo "\033[41m \033[7m\033[1m ERROR!! \033[0m \033[31m $errorMessage \033[0m\n\n";
    }
}



/*
|--------------------------------------------------------------------------
| คู่มือการใช้งาน Migration System
|--------------------------------------------------------------------------

1. การสร้าง Migration:
$migration = new Migration();

2. สร้างตารางพื้นฐาน:
$migration->createTable('users', function($table) {
    $table->id('รหัสผู้ใช้');
    $table->string('username', 100, 'ชื่อผู้ใช้');
    $table->string('email', 255, 'อีเมล')->unique();
    $table->timestamp('created_at', 'วันที่สร้าง');
});

3. ประเภทคอลัมน์ที่รองรับ:
// ตัวเลข
$table->id('คำอธิบาย');                    // INT AUTO_INCREMENT PRIMARY KEY
$table->tinyInteger('age', 'อายุ');        // TINYINT (-128 ถึง 127)
$table->smallInteger('year', 'ปี');        // SMALLINT (-32,768 ถึง 32,767)
$table->integer('amount', 'จำนวน');        // INT ปกติ
$table->bigInteger('views', 'ยอดวิว');      // BIGINT
$table->decimal('price', 10, 2, 'ราคา');   // DECIMAL(10,2)
$table->float('weight', 'น้ำหนัก');        // FLOAT
$table->double('distance', 'ระยะทาง');      // DOUBLE

// ข้อความ
$table->char('code', 3, 'รหัส');           // CHAR(3)
$table->string('name', 255, 'ชื่อ');       // VARCHAR(255)
$table->text('desc', 'รายละเอียด');         // TEXT
$table->mediumText('content', 'เนื้อหา');   // MEDIUMTEXT
$table->longText('data', 'ข้อมูล');        // LONGTEXT

// วันที่และเวลา
$table->date('birth_date', 'วันเกิด');      // DATE
$table->time('open_time', 'เวลาเปิด');      // TIME
$table->datetime('event_at', 'วันที่จัด');   // DATETIME
$table->timestamp('created_at', 'สร้างเมื่อ'); // TIMESTAMP
$table->year('grad_year', 'ปีที่จบ');       // YEAR

// พิเศษ
$table->boolean('active', 'สถานะ');         // TINYINT(1)
$table->enum('status', ['draft','published'], 'สถานะ'); // ENUM
$table->json('settings', 'การตั้งค่า');      // JSON

4. Modifiers (ตัวปรับแต่ง):
->nullable()      // อนุญาต NULL
->unique()        // ห้ามซ้ำ
->default(value)  // ค่าเริ่มต้น
->unsigned()      // เฉพาะค่าบวก
->index()         // สร้าง Index
->primary()       // Primary Key

5. ตัวอย่างการใช้งานจริง:
// สร้างตารางสินค้า
$migration->createTable('products', function($table) {
    $table->id('รหัสสินค้า');
    $table->string('name', 100, 'ชื่อสินค้า')->unique();
    $table->text('description', 'รายละเอียด')->nullable();
    $table->decimal('price', 10, 2, 'ราคา');
    $table->integer('stock', 'สินค้าคงเหลือ')->default(0);
    $table->integer('category_id', 'รหัสหมวดหมู่');
    $table->enum('status', ['active','inactive'], 'สถานะ');
    $table->timestamp('created_at', 'วันที่สร้าง');
    $table->foreignKey('category_id', 'categories', 'id', 'CASCADE', 'SET NULL');
});

6. Foreign Keys:
->foreignKey('คอลัมน์', 'ตารางอ้างอิง', 'คอลัมน์อ้างอิง', 'ON DELETE', 'ON UPDATE')

ON DELETE/UPDATE options:
- CASCADE = ลบ/อัพเดทตามต้นทาง
- SET NULL = กำหนดเป็น NULL
- RESTRICT = ห้ามลบถ้ามีการอ้างอิง
- NO ACTION = ไม่ทำอะไร
*/