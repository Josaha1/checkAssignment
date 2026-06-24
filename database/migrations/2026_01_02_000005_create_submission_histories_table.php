<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ประวัติการกระทำต่อ submission (append-only) — upload / graded / score_cleared / file_deleted
        Schema::create('submission_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete();
            $table->string('action');             // uploaded | graded | score_cleared | file_deleted
            $table->string('actor')->nullable();  // ชื่อผู้กระทำ (นักศึกษา/แอดมิน)
            $table->string('detail')->nullable(); // ชื่อไฟล์ / คะแนน
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_histories');
    }
};
