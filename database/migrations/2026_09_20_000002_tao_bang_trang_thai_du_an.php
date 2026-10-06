<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trang_thai_du_an', function (Blueprint $t) {
            $t->id();
            $t->string('ten', 150)->unique()->comment('giá trị lưu thẳng trong du_an.trang_thai');
            // Dùng để gộp thống kê (đã hoạt động / chậm tiến độ) mà không phụ thuộc
            // vào việc so khớp đúng nguyên văn tên trạng thái.
            $t->string('nhom', 20)->default('binh_thuong')->comment('binh_thuong | hoan_thanh | cham_tre');
            $t->unsignedSmallInteger('thu_tu')->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trang_thai_du_an');
    }
};
