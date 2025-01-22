<?php

use Core\Migration;

class create_web_usertable {
    public function up() {
        $migration = new Migration();
        $migration->createTable('web_user', function ($table) {
            $table->id('Primary key ของตาราง');
            $table->string('name', 100, 'ชื่อ');
            $table->string('surname', 100, 'นามสกุล');
            $table->string('email', 255, 'อีเมล')->unique('email');
            $table->string('password', 255, 'รหัสผ่าน');
            $table->string('token', 255, 'โทเค็น');
            $table->default('level', 'INT', 2, 'ค่าเริ่มต้นของระดับเป็น 2');
            $table->integer('status', 'สถานะ');
            $table->timestamps();
        }, 'ตารางผู้ใช้เว็บ');
    }

    public function down() {
        $migration = new Migration();
        $migration->dropTable('web_user');
    }
}


    /*
        |--------------------------------------------------------------------------------------------------------------------------
        | Migration Tutorial
        | ประเภทที่รองรับในการใช้งาน
        |--------------------------------------------------------------------------------------------------------------------------
        |
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
        |     $table->default('account_type', 'VARCHAR(50)', "'xxx'", 'ประเภทบัญชี'); // สร้างคอลัมน์ 'account_type' ชนิด VARCHAR(50) และมีค่าเริ่มต้นเป็น 'xxx'
        |
        */