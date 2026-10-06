<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loai_hinh_du_an', function (Blueprint $t) {
            $t->id();
            $t->string('ma', 30)->unique()->comment('mã ổn định, lưu trong du_an.loai_hinh');
            $t->string('ten', 150);
            $t->string('mau', 7)->comment('màu hex dùng trên bản đồ và biểu đồ');
            $t->unsignedSmallInteger('thu_tu')->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loai_hinh_du_an');
    }
};
