<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('du_an', function (Blueprint $t) {
            $t->string('ranh_gioi_dang', 10)->nullable()->after('ranh_gioi')
                ->comment("'vung' (khép kín) hoặc 'duong' (tuyến hở); chỉ có ý nghĩa khi ranh_gioi khác rỗng");
        });
    }

    public function down(): void
    {
        Schema::table('du_an', function (Blueprint $t) {
            $t->dropColumn('ranh_gioi_dang');
        });
    }
};
