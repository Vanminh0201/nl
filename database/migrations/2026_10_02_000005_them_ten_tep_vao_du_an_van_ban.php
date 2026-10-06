<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tên tệp gốc của văn bản chủ trương đầu tư tải lên ở trang chi tiết dự án — hiện khi văn bản chưa ghi số
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('du_an_van_ban', function (Blueprint $t) {
            $t->string('ten_tep', 255)->nullable()->after('tep');
        });
    }

    public function down(): void
    {
        Schema::table('du_an_van_ban', function (Blueprint $t) {
            $t->dropColumn('ten_tep');
        });
    }
};
