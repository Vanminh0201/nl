<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bảng báo cáo tiến độ cũ (mỗi lần nộp 1 dự án, 1 ô nội dung) chưa có dòng dữ liệu nào —
        // thay hẳn bằng báo cáo tuần theo mẫu văn bản: 1 báo cáo/chủ đầu tư/tuần, mỗi dự án 1 mục.
        Schema::dropIfExists('bao_cao_du_an');

        Schema::create('bao_cao_tuan', function (Blueprint $t) {
            $t->id();
            $t->foreignId('nguoi_bao_cao_id')->constrained('users')->cascadeOnDelete();
            $t->smallInteger('nam')->comment('Năm theo tuần ISO');
            $t->tinyInteger('tuan')->comment('Tuần ISO, thứ Hai đến Chủ nhật');
            $t->date('tu_ngay');
            $t->date('den_ngay');

            $t->string('co_quan_chu_quan')->nullable();
            $t->string('ten_don_vi')->nullable();
            $t->string('so_hieu', 100)->nullable();
            $t->string('dia_danh', 100)->nullable();
            $t->date('ngay_bao_cao')->nullable();
            $t->text('kinh_gui')->nullable();
            $t->text('gioi_thieu')->nullable();
            $t->text('noi_nhan')->nullable();
            $t->string('chuc_vu_ky')->nullable();
            $t->string('nguoi_ky')->nullable();

            $t->string('trang_thai', 20)->default('nhap');
            $t->timestamp('nop_luc')->nullable();
            $t->foreignId('nguoi_xu_ly_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('xu_ly_luc')->nullable();
            $t->text('y_kien_xu_ly')->nullable();
            $t->timestamps();

            $t->unique(['nguoi_bao_cao_id', 'nam', 'tuan']);
            $t->index(['nam', 'tuan']);
            $t->index('trang_thai');
        });

        Schema::create('bao_cao_tuan_du_an', function (Blueprint $t) {
            $t->id();
            $t->foreignId('bao_cao_tuan_id')->constrained('bao_cao_tuan')->cascadeOnDelete();
            $t->foreignId('du_an_id')->constrained('du_an')->cascadeOnDelete();

            $t->text('thu_tuc_da_xong')->nullable();
            $t->text('cong_viec_dang_lam')->nullable();
            $t->text('kho_khan')->nullable();
            $t->text('ke_hoach')->nullable();
            $t->tinyInteger('tien_do')->nullable();

            // Đơn vị tỷ đồng, như trong mẫu báo cáo
            $t->decimal('tong_muc_dau_tu', 15, 3)->nullable();
            $t->decimal('ke_hoach_von_nam', 15, 3)->nullable();
            $t->decimal('giai_ngan_nam', 15, 3)->nullable();
            $t->decimal('luy_ke_giai_ngan', 15, 3)->nullable();

            $t->boolean('pl_chu_truong')->default(false);
            $t->boolean('pl_dat_dai')->default(false);
            $t->boolean('pl_moi_truong')->default(false);
            $t->boolean('pl_pccc')->default(false);
            $t->boolean('pl_xay_dung')->default(false);
            $t->timestamps();

            $t->unique(['bao_cao_tuan_id', 'du_an_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bao_cao_tuan_du_an');
        Schema::dropIfExists('bao_cao_tuan');

        Schema::create('bao_cao_du_an', function (Blueprint $t) {
            $t->id();
            $t->foreignId('du_an_id')->constrained('du_an')->cascadeOnDelete();
            $t->foreignId('nguoi_bao_cao_id')->constrained('users')->cascadeOnDelete();
            $t->text('noi_dung')->nullable();
            $t->tinyInteger('tien_do');
            $t->boolean('pl_chu_truong')->default(false);
            $t->boolean('pl_dat_dai')->default(false);
            $t->boolean('pl_moi_truong')->default(false);
            $t->boolean('pl_pccc')->default(false);
            $t->boolean('pl_xay_dung')->default(false);
            $t->timestamps();
            $t->index(['du_an_id', 'created_at']);
        });
    }
};
