<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_code')->unique(); // รหัสนักศึกษา
            $table->string('full_name');               // ชื่อ-นามสกุล (รวมคำนำหน้า)
            $table->string('email')->unique();         // อีเมลสถาบัน = ใช้ login
            $table->string('password');                // hash (ตั้งต้น = รหัสนักศึกษา)
            $table->string('faculty')->nullable();     // คณะ
            $table->string('major')->nullable();       // สาขา
            $table->string('program')->nullable();     // รอบ/หลักสูตร
            $table->string('study_group')->nullable(); // กลุ่มเรียน
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
