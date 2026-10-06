<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Văn bản lưu trữ theo từng hồ sơ pháp lý của dự án (1 hồ sơ có thể có nhiều tệp). Tệp nằm trong
// storage/app/private, chỉ mở được qua đường dẫn có kiểm tra quyền xem dự án.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('du_an_ho_so_tep', function (Blueprint $t) {
            $t->id();
            $t->foreignId('du_an_id')->constrained('du_an')->cascadeOnDelete();
            $t->string('loai', 30);
            $t->string('ten_tep');
            $t->string('duong_dan');
            $t->unsignedBigInteger('kich_thuoc')->default(0);
            $t->string('so_van_ban', 100)->nullable();
            $t->date('ngay_van_ban')->nullable();
            $t->foreignId('nguoi_tai_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['du_an_id', 'loai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('du_an_ho_so_tep');
    }
};
