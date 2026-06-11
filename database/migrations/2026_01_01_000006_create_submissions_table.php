<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('link'); // ลิงก์ google drive / online storage เท่านั้น
            $table->decimal('score', 6, 2)->nullable(); // null = ยังไม่ตรวจ
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();
            $table->unique(['assignment_id', 'student_id']); // 1 งาน ส่งได้ 1 ครั้ง/คน
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
