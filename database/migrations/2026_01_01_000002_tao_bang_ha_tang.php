<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lop_ha_tang', function (Blueprint $t) {
            $t->id();
            $t->string('ten');
            $t->string('nguon')->nullable()->comment('nguồn dữ liệu, VD: OpenInfraMap');
            $t->string('ten_file')->nullable();
            $t->unsignedInteger('so_doi_tuong')->default(0);
            $t->boolean('hien_thi')->default(true);
            $t->foreignId('nguoi_tao_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('doi_tuong_ha_tang', function (Blueprint $t) {
            $t->id();
            $t->foreignId('lop_id')->constrained('lop_ha_tang')->cascadeOnDelete();
            $t->string('ten')->nullable();
            $t->string('loai', 20)->index()->comment('tram | duong_day | nha_may | khac');
            $t->unsignedSmallInteger('dien_ap')->nullable()->index()->comment('kV');
            $t->json('hinh_hoc')->comment('geometry theo chuẩn GeoJSON');
            $t->json('thuoc_tinh')->nullable();

            $t->decimal('tam_vi_do', 10, 7)->nullable();
            $t->decimal('tam_kinh_do', 10, 7)->nullable();
            $t->decimal('min_vi_do', 10, 7)->nullable();
            $t->decimal('max_vi_do', 10, 7)->nullable();
            $t->decimal('min_kinh_do', 10, 7)->nullable();
            $t->decimal('max_kinh_do', 10, 7)->nullable();

            $t->index(['min_vi_do', 'max_vi_do', 'min_kinh_do', 'max_kinh_do'], 'idx_ha_tang_bbox');
            $t->index(['loai', 'dien_ap', 'tam_vi_do', 'tam_kinh_do'], 'idx_ha_tang_tram');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doi_tuong_ha_tang');
        Schema::dropIfExists('lop_ha_tang');
    }
};
