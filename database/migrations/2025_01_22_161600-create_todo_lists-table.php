<?php

use Core\Migration;

class create_todo_liststable {
    public function up() {
        $migration = new Migration();

        $migration->createTable('todo_lists', function ($table) {
            $table->id('รหัส TODO');
            $table->string('title', 255, 'หัวข้อ');
            $table->text('description', 'รายละเอียด')->nullable();
            $table->integer('category_id', 'รหัสหมวดหมู่');
            $table->integer('member_id', 'สร้างโดย');
            $table->integer('assigned_to', 'มอบหมายให้')->nullable();
            $table->enum('priority', ['low', 'medium', 'high'], 'ความสำคัญ')->default('medium');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'], 'สถานะ')->default('pending');
            $table->dateTime('due_date', 'กำหนดเสร็จ')->nullable();
            $table->dateTime('completed_date', 'วันที่เสร็จ')->nullable();
            $table->timestamps();

            // Foreign Keys
            $table->foreign('category_id', 'id', 'todo_categories', 'CASCADE', 'CASCADE');
            $table->foreign('member_id', 'id', 'web_user', 'CASCADE', 'CASCADE');
            $table->foreign('assigned_to', 'id', 'web_user', 'SET NULL', 'SET NULL');
        }, 'ตารางรายการ TODO');
    }
    // Drop the table
    public function down() {
        $migration = new Migration();
        $migration->dropTable('todo_lists');
    }
}