<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tháng dự án nguồn điện bắt đầu phát điện lên lưới (YYYY-MM): dự án đang chạy thử, chưa có chứng chỉ
// vận hành thương mại (PAC/COD) vẫn báo cáo sản lượng điện tháng từ tháng này.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('du_an', function (Blueprint $t) {
            $t->string('phat_dien_tu', 7)->nullable()->after('tien_do_van_hanh');
        });
    }

    public function down(): void
    {
        Schema::table('du_an', function (Blueprint $t) {
            $t->dropColumn('phat_dien_tu');
        });
    }
};
