<?php

namespace Core;

use Core\Database;

class Seeder {
    private \Medoo\Medoo $db;
    private string $table;
    /** @var array<int, array<string, mixed>> $data */
    private array $data = [];

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * ตั้งค่าชื่อตาราง
     * @param string $table
     * @return self
     */
    public function table(string $table): self {
        $this->table = $table;
        return $this;
    }

    /**
     * เพิ่มข้อมูลที่ต้องการ seed
     * @param array<string, mixed> $data
     * @return self
     */
    public function add(array $data): self {
        $this->data[] = $data;
        return $this;
    }

    /**
     * รันการ insert ข้อมูล
     * @return void
     */
    public function run(): void {
        foreach ($this->data as $data) {
            try {
                $this->db->insert($this->table, $data);
                echo "Inserted data into `$this->table` successfully.\n";
            } catch (\PDOException $e) {
                echo "\033[41m ERROR!! \033[93m " . $e->getMessage() . " \033[0m \n";
                exit();
            }
        }
    }
}

// การใช้งาน
// $seeder = new Seeder();
// $seeder->table('web_admin')
//     ->add([
//         'idsale' => 'S001',
//         'sub_sale' => 1,
//         'pname' => 'John',
//         'sname' => 'Doe',
//         'username' => 'johndoe',
//         'password' => password_hash('password123', PASSWORD_DEFAULT),
//         'level' => 2,
//         'telephone' => '0123456789',
//         'mobile' => '0987654321',
//         'email' => 'john@example.com',
//         'images' => 'john.jpg',
//         'department' => 1,
//         'position' => 1,
//         'labs' => 1,
//         'detail' => 'Example admin user',
//         'status' => true,
//         'mem_id' => 1,
//         'customer_id' => 0,
//         'linetoken' => 'token1234',
//         'mem_add' => 1,
//         'mem_edit' => 1,
//         'mem_del' => 0
//     ])
//     // เพิ่มข้อมูลเพิ่มเติมตามต้องการ
//     ->run();