<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('du_an', function (Blueprint $t) {
            $t->dropColumn(['tram_gan_nhat', 'tram_dien_ap', 'khoang_cach_tram']);
        });

        // Chỉ mục này chỉ phục vụ truy vấn "trạm gần nhất" đã bỏ, không còn câu truy vấn nào dùng tới.
        Schema::table('doi_tuong_ha_tang', function (Blueprint $t) {
            $t->dropIndex('idx_ha_tang_tram');
        });
    }

    public function down(): void
    {
        Schema::table('du_an', function (Blueprint $t) {
            $t->string('tram_gan_nhat')->nullable();
            $t->unsignedSmallInteger('tram_dien_ap')->nullable();
            $t->decimal('khoang_cach_tram', 8, 2)->nullable()->index();
        });

        Schema::table('doi_tuong_ha_tang', function (Blueprint $t) {
            $t->index(['loai', 'dien_ap', 'tam_vi_do', 'tam_kinh_do'], 'idx_ha_tang_tram');
        });
    }
};
