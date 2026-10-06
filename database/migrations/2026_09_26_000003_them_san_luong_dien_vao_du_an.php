<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('du_an', function (Blueprint $t) {
            // Sản lượng điện thực phát trong năm gần nhất (kWh) — chỉ có ý nghĩa với dự án điện
            // đã vận hành, lấy từ báo cáo thực tế, không phải công suất lắp đặt (cong_suat_mw).
            $t->decimal('san_luong_dien', 15, 2)->nullable()->after('cong_suat_mw');
        });
    }

    public function down(): void
    {
        Schema::table('du_an', function (Blueprint $t) {
            $t->dropColumn('san_luong_dien');
        });
    }
};
