<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Ẩn/hiện từng đối tượng (từng xuất tuyến, từng đường dây đấu nối) trong lớp hạ tầng trên trang Bản đồ
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doi_tuong_ha_tang', function (Blueprint $t) {
            $t->boolean('hien_thi')->default(true)->after('chieu_dai_m');
        });
    }

    public function down(): void
    {
        Schema::table('doi_tuong_ha_tang', function (Blueprint $t) {
            $t->dropColumn('hien_thi');
        });
    }
};
