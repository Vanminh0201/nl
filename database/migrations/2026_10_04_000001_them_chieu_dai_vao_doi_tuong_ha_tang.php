<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Chiều dài đo trên bản đồ (mét) của đối tượng dạng đường — để menu Lưới điện, popup hiện chiều dài từng tuyến
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doi_tuong_ha_tang', function (Blueprint $t) {
            $t->decimal('chieu_dai_m', 12, 1)->nullable()->after('thuoc_tinh');
        });
    }

    public function down(): void
    {
        Schema::table('doi_tuong_ha_tang', function (Blueprint $t) {
            $t->dropColumn('chieu_dai_m');
        });
    }
};
