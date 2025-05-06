<?php

use Core\Migration;

class create_todo_categoriestable {
    public function up() {
        $migration = new Migration();
        // สร้างตาราง todo_categories
        $migration->createTable('todo_categories', function ($table) {
            $table->id('รหัสหมวดหมู่');
            $table->string('name', 100, 'ชื่อหมวดหมู่');
            $table->text('description', 'รายละเอียด')->nullable();
            $table->integer('member_id', 'สร้างโดย');
            $table->boolean('is_active', 'สถานะการใช้งาน')->default(0, 'BOOLEAN', true);
            $table->timestamps();
            $table->foreign('member_id', 'id', 'web_user', 'CASCADE', 'CASCADE');
        }, 'ตารางหมวดหมู่ TODO');
    }
    // Drop the table
    public function down() {
        $migration = new Migration();
        $migration->dropTable('todo_categories');
    }
}