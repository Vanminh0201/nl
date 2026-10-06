<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Nhật ký hoạt động của trang web (trang Nhật ký, chỉ quản trị xem): đăng nhập, thay đổi dữ liệu,
// tải/xuất tệp, xem trang
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nhat_ky', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // Giữ tên, vai trò lúc ghi — tài khoản đổi tên hoặc bị xoá vẫn đọc được nhật ký cũ
            $t->string('ten_nguoi_dung')->nullable();
            $t->string('vai_tro', 30)->nullable();
            $t->string('nhom', 20);
            $t->string('hanh_dong');
            $t->string('doi_tuong', 500)->nullable();
            $t->string('lien_ket', 500)->nullable();
            $t->json('chi_tiet')->nullable();
            $t->string('phuong_thuc', 10)->nullable();
            $t->string('duong_dan', 500)->nullable();
            $t->unsignedSmallInteger('ma_phan_hoi')->nullable();
            $t->string('ip', 45)->nullable();
            $t->string('trinh_duyet')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index('created_at');
            $t->index(['nhom', 'created_at']);
            $t->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nhat_ky');
    }
};
