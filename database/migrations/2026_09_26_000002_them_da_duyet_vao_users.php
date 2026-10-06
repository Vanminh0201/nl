<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            // Mặc định true để không ảnh hưởng các tài khoản đã có (admin tạo tay) — chỉ tài
            // khoản chủ đầu tư tự đăng ký mới đặt false, chờ quản trị viên duyệt mới đăng nhập được.
            $t->boolean('da_duyet')->default(true)->after('vai_tro');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn('da_duyet');
        });
    }
};
