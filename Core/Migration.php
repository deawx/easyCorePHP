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
        $query = "DROP TABLE IF EXISTS `$table`";
        try {
            $this->db->query($query);
            echo "\033[42m \033[7m\033[1m Successfully!! \033[0m \033[31m Droped table : `$table` \033[0m\n\n";
            // echo "Table `$table` dropped successfully.\n";
        } catch (\PDOException $e) {
            $this->handleError($e);
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

    public function default(string $name = '', string $type = '', mixed $default = null, string $comment = ''): self {
        if (empty($name) && empty($type)) {
            // ใช้สำหรับ Method Chaining
            $lastColumn = array_pop($this->columns);
            if ($lastColumn) {
                $defaultValue = is_string($default) ? "'$default'" : $default;
                $definition = $lastColumn['definition'] . " DEFAULT $defaultValue";
                $this->columns[] = ['definition' => $definition];
            }
        } else {
            // ใช้สำหรับสร้าง column with default โดยตรง
            $defaultValue = is_string($default) ? "'$default'" : $default;
            $this->columns[] = ['definition' => "`$name` $type NOT NULL DEFAULT $defaultValue" . $this->addComment($comment)];
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
        try {
            $this->db->query($query);
            echo "\033[42m \033[7m\033[1m Successfully!! \033[0m \033[32m Create taable : `$this->table` \033[0m\n\n";
            // echo "Table `$this->table` created successfully.\n";
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
| คู่มือการใช้งาน Migration (ภาษาไทย)
|--------------------------------------------------------------------------
|
| 1. การสร้างตารางพื้นฐาน:
|
| // สร้างตารางผู้ใช้งาน
| $migration = new Migration();
| $migration->createTable('users', function($table) {
|     $table->id('รหัสผู้ใช้');                     // สร้าง PK อัตโนมัติ
|     $table->string('username', 100, 'ชื่อผู้ใช้');  // VARCHAR(100)
|     $table->string('email', 255, 'อีเมล');         // VARCHAR(255)
|     $table->timestamp('created_at', 'วันที่สร้าง'); // TIMESTAMP
| });
|
| 2. ประเภทคอลัมน์ที่รองรับ:
|
| $table->id('คำอธิบาย');                // INT AUTO_INCREMENT PRIMARY KEY
| $table->integer('อายุ', 'อายุผู้ใช้');   // INT ธรรมดา
| $table->string('ชื่อ', 'ชื่อผู้ใช้');    // VARCHAR(255)
| $table->text('เนื้อหา', 'บทความ');      // TEXT เก็บข้อความยาว
| $table->float('ราคา', 'ราคาสินค้า');    // FLOAT ทศนิยม
| $table->boolean('สถานะ', 'เปิด/ปิด');   // TINYINT(1) เก็บค่า true/false
| $table->datetime('เวลา', 'เวลาสร้าง');   // DATETIME
|
| 3. ตัวปรับแต่งคอลัมน์:
|
| $table->string('email')->unique();         // ห้ามข้อมูลซ้ำ
| $table->integer('points')->default(0);     // กำหนดค่าเริ่มต้น
| $table->string('address')->nullable();     // อนุญาตให้เป็น NULL
|
| 4. การสร้าง Foreign Key:
|
| // สร้างตารางสินค้า
| $migration->createTable('products', function($table) {
|     $table->id('รหัสสินค้า');
|     $table->integer('category_id', 'รหัสหมวดหมู่');
|     $table->string('name', 100, 'ชื่อสินค้า');
|     // สร้าง FK เชื่อมกับตาราง categories
|     $table->foreignKey('category_id', 'categories', 'id', 'CASCADE', 'SET NULL');
| });
|
| 5. ตัวอย่างการใช้งานจริง:
|
| // สร้างตารางคำสั่งซื้อ
| $migration->createTable('orders', function($table) {
|     $table->id('รหัสคำสั่งซื้อ');
|     $table->integer('user_id', 'รหัสผู้ซื้อ');
|     $table->float('total', 'ยอดรวม')->default(0.00);
|     $table->string('status', 50, 'สถานะ')->default('pending');
|     $table->timestamp('order_date', 'วันที่สั่งซื้อ');
|     $table->text('note', 'หมายเหตุ')->nullable();
|     $table->foreignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
| });
|
| หมายเหตุ:
| - ควรตั้งชื่อตารางเป็นภาษาอังกฤษและเป็นพหูพจน์
| - สามารถใส่คำอธิบายภาษาไทยในทุกคอลัมน์
| - ควรสร้างตารางหลักก่อนสร้าง Foreign Key เสมอ
| - CASCADE หมายถึง เมื่อลบข้อมูลหลัก ข้อมูลที่เชื่อมโยงจะถูกลบด้วย
| - SET NULL หมายถึง เมื่อลบข้อมูลหลัก ข้อมูลที่เชื่อมโยงจะกลายเป็น NULL
*/

/*
|--------------------------------------------------------------------------
| ประเภทคอลัมน์ที่รองรับทั้งหมด
|--------------------------------------------------------------------------
|
| 1. คอลัมน์พื้นฐาน:
| $table->id('คำอธิบาย');                    // INT AUTO_INCREMENT PRIMARY KEY
| $table->primaryKey('id', 'คำอธิบาย');      // กำหนด Primary Key เอง
| $table->foreignKey('user_id', 'users', 'id'); // สร้าง Foreign Key
|
| 2. ตัวเลข:
| $table->tinyInteger('age', 'อายุ');        // TINYINT (-128 ถึง 127)
| $table->smallInteger('year', 'ปี');        // SMALLINT (-32,768 ถึง 32,767)
| $table->integer('amount', 'จำนวน');        // INT (-2^31 ถึง 2^31-1)
| $table->bigInteger('population', 'ประชากร'); // BIGINT (-2^63 ถึง 2^63-1)
| $table->decimal('price', 10, 2, 'ราคา');    // DECIMAL(10,2) ทศนิยมแม่นยำ
| $table->float('weight', 'น้ำหนัก');        // FLOAT ทศนิยม
| $table->double('distance', 'ระยะทาง');      // DOUBLE ทศนิยมความละเอียดสูง
|
| 3. ข้อความ:
| $table->char('code', 3, 'รหัส');           // CHAR(3) ความยาวคงที่
| $table->string('name', 255, 'ชื่อ');       // VARCHAR(255) ความยาวไม่เกิน 255
| $table->text('description', 'รายละเอียด');   // TEXT ข้อความยาว
| $table->mediumText('content', 'เนื้อหา');   // MEDIUMTEXT (~16MB)
| $table->longText('data', 'ข้อมูล');        // LONGTEXT (~4GB)
|
| 4. วันที่และเวลา:
| $table->date('birth_date', 'วันเกิด');      // DATE (YYYY-MM-DD)
| $table->time('start_time', 'เวลาเริ่ม');    // TIME (HH:MM:SS)
| $table->datetime('event_at', 'วันที่จัด');   // DATETIME (YYYY-MM-DD HH:MM:SS)
| $table->timestamp('created_at', 'สร้างเมื่อ'); // TIMESTAMP ตามเวลา UTC
| $table->year('grad_year', 'ปีที่จบ');       // YEAR (1901 ถึง 2155)
|
| 5. พิเศษ:
| $table->boolean('active', 'สถานะ');        // TINYINT(1) เก็บ true/false
| $table->enum('status', ['draft','published']); // ENUM ค่าที่กำหนด
| $table->json('settings', 'การตั้งค่า');      // JSON เก็บข้อมูล JSON
| $table->binary('file', 'ไฟล์');            // BINARY เก็บไฟล์ไบนารี
|
| 6. ตัวอย่างการใช้งาน:
| $migration->createTable('products', function($table) {
|     $table->id('รหัสสินค้า');                      // Primary Key อัตโนมัติ
|     $table->string('name', 100, 'ชื่อสินค้า');      // VARCHAR(100)
|     $table->decimal('price', 10, 2, 'ราคา');       // ราคาทศนิยม 2 ตำแหน่ง
|     $table->text('description', 'รายละเอียด');      // ข้อความยาว
|     $table->enum('status', ['active','inactive']);  // สถานะที่กำหนด
|     $table->timestamp('created_at', 'สร้างเมื่อ');   // วันที่สร้าง
| });
*/


/*
|--------------------------------------------------------------------------
| Migration Tutorial
|--------------------------------------------------------------------------
|
| $migration = new Migration();
|
| // สร้างตารางใหม่ 'demo'
| $migration->createTable('demo', function($table) {
|     $table->id('ID หลัก'); // สร้างคอลัมน์ 'id' เป็น Primary Key และ Auto Increment
|     $table->string('username', 255, 'ชื่อผู้ใช้งาน'); // สร้างคอลัมน์ 'username' ชนิด VARCHAR(255) และไม่เป็น NULL
|     $table->string('email', 255, 'อีเมล'); // สร้างคอลัมน์ 'email' ชนิด VARCHAR(255) และไม่เป็น NULL
|     $table->string('password', 255, 'รหัสผ่าน'); // สร้างคอลัมน์ 'password' ชนิด VARCHAR(255) และไม่เป็น NULL
|     $table->integer('age', 'อายุ'); // สร้างคอลัมน์ 'age' ชนิด INT และไม่เป็น NULL
|     $table->tinyint('status', 'สถานะ'); // สร้างคอลัมน์ 'status' ชนิด TINYINT และไม่เป็น NULL
|     $table->bigint('total_points', 'คะแนนรวม'); // สร้างคอลัมน์ 'total_points' ชนิด BIGINT และไม่เป็น NULL
|     $table->text('bio', 'ข้อมูลส่วนตัว'); // สร้างคอลัมน์ 'bio' ชนิด TEXT และไม่เป็น NULL
|     $table->boolean('is_active', 'สถานะการใช้งาน'); // สร้างคอลัมน์ 'is_active' ชนิด BOOLEAN และไม่เป็น NULL
|     $table->decimal('balance', 10, 2, 'ยอดเงินคงเหลือ'); // สร้างคอลัมน์ 'balance' ชนิด DECIMAL(10,2) และไม่เป็น NULL
|     $table->date('birthdate', 'วันเกิด'); // สร้างคอลัมน์ 'birthdate' ชนิด DATE และไม่เป็น NULL
|     $table->dateTime('created_at', 'สร้างเมื่อ'); // สร้างคอลัมน์ 'created_at' ชนิด DATETIME และไม่เป็น NULL
|     $table->time('login_time', 'เวลาเข้าสู่ระบบ'); // สร้างคอลัมน์ 'login_time' ชนิด TIME และไม่เป็น NULL
|     $table->timestamp('updated_at', 'อัปเดตเมื่อ'); // สร้างคอลัมน์ 'updated_at' ชนิด TIMESTAMP และไม่เป็น NULL
|     $table->char('gender', 1, 'เพศ'); // สร้างคอลัมน์ 'gender' ชนิด CHAR(1) และไม่เป็น NULL
|     $table->float('height', 5, 2, 'ส่วนสูง'); // สร้างคอลัมน์ 'height' ชนิด FLOAT(5,2) และไม่เป็น NULL
|     $table->double('weight', 8, 2, 'น้ำหนัก'); // สร้างคอลัมน์ 'weight' ชนิด DOUBLE(8,2) และไม่เป็น NULL
|     $table->unique('email'); // สร้าง UNIQUE constraint ที่คอลัมน์ 'email'
|     $table->index('username'); // สร้าง INDEX ที่คอลัมน์ 'username'
|     $table->timestamps(); // สร้างคอลัมน์ 'created_at' และ 'updated_at' ชนิด TIMESTAMP
|     $table->nullable('nickname', 'VARCHAR(50)'); // สร้างคอลัมน์ 'nickname' ชนิด VARCHAR(50) และรองรับค่า NULL ได้
|     $table->default('account_type', 'VARCHAR(50)', "'standard'", 'ประเภทบัญชี'); // สร้างคอลัมน์ 'account_type' ชนิด VARCHAR(50) และมีค่าเริ่มต้นเป็น 'standard'
| }, 'ตารางตัวอย่าง');
|
| // แก้ไขตาราง 'demo'
| $migration->modifyTable('demo', function($table) {
|     $table->string('profile_picture', 255, 'รูปประจำตัว'); // เพิ่มคอลัมน์ 'profile_picture' ชนิด VARCHAR(255) และไม่เป็น NULL
|     $table->modifyColumn('username', 'VARCHAR(512)', 'ชื่อผู้ใช้งานยาวขึ้น'); // แก้ไขคอลัมน์ 'username' ให้ยาวขึ้นเป็น 512 ตัวอักษร
| });
|
| // ลบตาราง 'demo'
| $migration->dropTable('demo');
|
| // เปลี่ยนชื่อตารางจาก 'demo' เป็น 'members'
| $migration->renameTable('demo', 'members');
|
| // เปลี่ยนชื่อคอลัมน์จาก 'username' เป็น 'user_name'
| $migration->renameColumn('username', 'user_name');
|
| // แก้ไขคอลัมน์ 'email' ให้รองรับค่า NULL ได้
| $migration->modifyColumn('email', 'VARCHAR(255) NULL', 'อีเมลสามารถเป็นค่า NULL ได้');
|
| // ลบคอลัมน์ 'profile_picture'
| $migration->dropColumn('profile_picture');
|
| // เพิ่มดัชนี (index) ที่คอลัมน์ 'email'
| $migration->addIndex('email');
|
| // ลบดัชนี (index) ที่คอลัมน์ 'email'
| $migration->dropIndex('email');
|
| // เพิ่ม Foreign Key ที่คอลัมน์ 'role_id' เชื่อมโยงกับตาราง 'roles' ที่คอลัมน์ 'id'
| $migration->foreign('role_id', 'id', 'roles');
|
| // ลบ Foreign Key ที่ชื่อ 'role_id_foreign'
| $migration->dropForeign('role_id_foreign');
|
*/