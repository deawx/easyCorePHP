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