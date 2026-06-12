<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // เปลี่ยนการส่งงานเป็นแนบไฟล์ → link ไม่บังคับแล้ว
        Schema::table('submissions', function (Blueprint $table) {
            $table->string('link')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->string('link')->nullable(false)->change();
        });
    }
};
