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
            $table->string('student_code')->unique(); // รหัสนักศึกษา = username
            $table->string('full_name');
            $table->date('birthdate'); // = รหัสผ่าน (เทียบตรง ๆ ไม่ hash ตามสเปก)
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
