<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            // null = theo mặc định của vai trò; mảng = danh sách mục quản trị cấp riêng cho tài khoản
            $t->json('quyen_xem')->nullable()->after('da_duyet');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn('quyen_xem');
        });
    }
};
