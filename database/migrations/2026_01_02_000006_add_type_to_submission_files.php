<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ชนิด row: file (ไฟล์บน Drive) | link (ลิงก์ที่นักศึกษาแปะ) — เก็บรวมตารางเดียวให้แสดง/ลบผ่าน flow เดิม
        Schema::table('submission_files', function (Blueprint $table) {
            $table->string('type')->default('file');
        });
        // link row ไม่มีไฟล์บน Drive → drive_file_id ต้องเป็น null ได้
        Schema::table('submission_files', function (Blueprint $table) {
            $table->string('drive_file_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // ไม่คืน drive_file_id เป็น NOT NULL — ถ้ามี link row (null) อยู่จะ tighten ไม่ได้
        Schema::table('submission_files', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
