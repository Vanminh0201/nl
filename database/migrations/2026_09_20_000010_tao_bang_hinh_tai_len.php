<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hinh_tai_len', function (Blueprint $t) {
            $t->id();
            $t->string('ten', 100);
            $t->string('tep')->comment('Đường dẫn file SVG/PNG trong public/bieu-tuong-tai-len, admin tự tải lên');
            $t->unsignedSmallInteger('thu_tu')->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hinh_tai_len');
    }
};
