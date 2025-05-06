<?php

use Core\Migration;

class create_todostable {
    public function up() {
        $migration = new Migration();
        $migration->createTable('todos', function ($table) {
            $table->id('รหัส TODO');
            // $table->string('title', 255, 'Todo title');
            $table->string('title', 255, 'หัวข้อ');
            // $table->text('description', 'Todo description')->nullable();
            $table->text('description', 'รายละเอียด')->nullable();
            // $table->boolean('completed', 'Completion status')->default(false);
            $table->boolean('completed', 'สถานะการใช้งาน')->default(0, 'BOOLEAN', true);
            $table->timestamps();
        }, 'Tables Comment Demo');
    }
    // Drop the table
    public function down() {
        $migration = new Migration();
        $migration->dropTable('todos');
    }
}