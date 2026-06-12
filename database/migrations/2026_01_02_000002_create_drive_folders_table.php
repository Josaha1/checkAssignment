<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // cache: parent_id + ชื่อโฟลเดอร์ → folder id บน Drive (กันสร้างซ้ำ/ลด API call)
        Schema::create('drive_folders', function (Blueprint $table) {
            $table->id();
            $table->string('cache_key')->unique(); // parentId|name
            $table->string('folder_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drive_folders');
    }
};
