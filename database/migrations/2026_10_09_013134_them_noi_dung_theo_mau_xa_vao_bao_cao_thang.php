<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Báo cáo tháng theo mẫu "Tình hình triển khai các dự án năng lượng trên địa bàn tỉnh" (nhóm theo xã):
 * thêm các cột của mẫu vào dòng báo cáo của chủ đầu tư, và bảng lưu nội dung Sở biên tập cho từng dự án
 * trong tháng (kể cả dự án chưa có chủ đầu tư báo cáo). Cột cũ giữ nguyên để báo cáo đã gửi vẫn xem được.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bao_cao_thang_du_an', function (Blueprint $t) {
            $t->text('du_kien_khoi_cong')->nullable()->after('chu_dau_tu');
            $t->text('du_kien_hoan_thanh')->nullable()->after('du_kien_khoi_cong');
            $t->text('kho_khan')->nullable()->after('tinh_hinh')->comment('Khó khăn vướng mắc');
            $t->text('kien_nghi_xa')->nullable()->after('kho_khan')->comment('Kiến nghị xử lý — xã/phường');
            $t->text('kien_nghi_so')->nullable()->after('kien_nghi_xa')->comment('Kiến nghị xử lý — sở, ngành');
        });

        Schema::create('bao_cao_thang_tong_hop', function (Blueprint $t) {
            $t->id();
            $t->unsignedSmallInteger('nam');
            $t->unsignedTinyInteger('thang');
            $t->foreignId('du_an_id')->constrained('du_an')->cascadeOnDelete();
            $t->text('du_kien_khoi_cong')->nullable();
            $t->text('du_kien_hoan_thanh')->nullable();
            $t->text('tinh_hinh')->nullable();
            $t->text('kho_khan')->nullable();
            $t->text('kien_nghi_xa')->nullable();
            $t->text('kien_nghi_so')->nullable();
            $t->foreignId('nguoi_sua_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();

            $t->unique(['nam', 'thang', 'du_an_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bao_cao_thang_tong_hop');
        Schema::table('bao_cao_thang_du_an', function (Blueprint $t) {
            $t->dropColumn(['du_kien_khoi_cong', 'du_kien_hoan_thanh', 'kho_khan', 'kien_nghi_xa', 'kien_nghi_so']);
        });
    }
};
