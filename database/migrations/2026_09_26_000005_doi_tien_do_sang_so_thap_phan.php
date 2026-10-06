<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Tiến độ các dự án lớn thường báo tới 2 chữ số thập phân (VD 99,65%) — cột số nguyên làm tròn sai
    public function up(): void
    {
        Schema::table('du_an', function (Blueprint $t) {
            $t->decimal('tien_do', 5, 2)->nullable()->change();
        });
        Schema::table('bao_cao_tuan_du_an', function (Blueprint $t) {
            $t->decimal('tien_do', 5, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('du_an', function (Blueprint $t) {
            $t->unsignedTinyInteger('tien_do')->nullable()->change();
        });
        Schema::table('bao_cao_tuan_du_an', function (Blueprint $t) {
            $t->tinyInteger('tien_do')->nullable()->change();
        });
    }
};
