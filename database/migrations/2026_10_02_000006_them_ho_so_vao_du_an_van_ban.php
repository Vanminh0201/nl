<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Văn bản của cả 8 hồ sơ pháp lý dùng chung bảng du_an_van_ban; ho_so = mã cột pl_… (DuAn::HO_SO_PHAP_LY).
// Văn bản đang có đều là văn bản chủ trương đầu tư.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('du_an_van_ban', function (Blueprint $t) {
            $t->string('ho_so', 30)->default('pl_chu_truong')->after('du_an_id');
            $t->unsignedBigInteger('kich_thuoc')->nullable()->after('ten_tep');
            $t->index(['du_an_id', 'ho_so']);
        });
    }

    public function down(): void
    {
        Schema::table('du_an_van_ban', function (Blueprint $t) {
            $t->dropIndex(['du_an_id', 'ho_so']);
            $t->dropColumn(['ho_so', 'kich_thuoc']);
        });
    }
};
