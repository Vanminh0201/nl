<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Chế độ báo cáo của từng tài khoản chủ đầu tư (trang Báo cáo → Cài đặt): tuần, tháng hoặc cả hai
        Schema::table('users', function (Blueprint $t) {
            $t->boolean('bc_tuan')->default(true)->after('quyen_xem');
            $t->boolean('bc_thang')->default(true)->after('bc_tuan');
        });

        // Cài đặt dạng khoá – giá trị do quản trị chỉnh trên giao diện (hạn nộp báo cáo tháng, ghi chú tổng hợp…)
        Schema::create('cai_dat', function (Blueprint $t) {
            $t->string('khoa', 120)->primary();
            $t->text('gia_tri')->nullable();
            $t->timestamps();
        });

        // Báo cáo tháng theo mẫu bảng "Tình hình triển khai quy hoạch điện VIII…": 1 báo cáo/chủ đầu
        // tư/tháng, mỗi dự án 1 dòng với đúng các cột của mẫu.
        Schema::create('bao_cao_thang', function (Blueprint $t) {
            $t->id();
            $t->foreignId('nguoi_bao_cao_id')->constrained('users')->cascadeOnDelete();
            $t->smallInteger('nam');
            $t->tinyInteger('thang');
            $t->string('ten_don_vi')->nullable();
            $t->string('nguoi_lap')->nullable();
            $t->string('dien_thoai', 50)->nullable();

            $t->string('trang_thai', 20)->default('nhap');
            $t->timestamp('nop_luc')->nullable();
            $t->foreignId('nguoi_xu_ly_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('xu_ly_luc')->nullable();
            $t->text('y_kien_xu_ly')->nullable();
            $t->timestamps();

            $t->unique(['nguoi_bao_cao_id', 'nam', 'thang']);
            $t->index(['nam', 'thang']);
            $t->index('trang_thai');
        });

        Schema::create('bao_cao_thang_du_an', function (Blueprint $t) {
            $t->id();
            $t->foreignId('bao_cao_thang_id')->constrained('bao_cao_thang')->cascadeOnDelete();
            $t->foreignId('du_an_id')->constrained('du_an')->cascadeOnDelete();

            $t->text('dia_diem')->nullable();
            $t->string('cong_suat', 255)->nullable()->comment('MW, hoặc MVA/km với dự án lưới điện');
            $t->text('chu_dau_tu')->nullable();
            $t->text('phap_ly')->nullable()->comment('Tình trạng pháp lý dự án');
            $t->text('tien_do_cod')->nullable()->comment('Tiến độ vận hành COD theo chủ trương đầu tư');
            $t->text('tien_do_thuc_te')->nullable()->comment('Tiến độ dự kiến đưa vào vận hành thực tế');
            $t->smallInteger('cod_nam')->nullable()->comment('Năm dự kiến vận hành thương mại — để tổng hợp ghi chú theo năm');
            $t->string('cod_ky', 10)->nullable()->comment('T1..T12 (tháng) hoặc Q1..Q4 (quý) của năm COD');
            $t->text('tinh_hinh')->nullable()->comment('Tình hình triển khai thực hiện, khó khăn vướng mắc');
            $t->text('phuong_an')->nullable()->comment('Phương án xử lý, đề xuất kiến nghị');
            $t->text('ghi_chu')->nullable();
            $t->string('nhom', 30)->nullable()->comment('Nhóm dự án trong bảng tổng hợp của Sở (I, II…)');
            $t->timestamps();

            $t->unique(['bao_cao_thang_id', 'du_an_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bao_cao_thang_du_an');
        Schema::dropIfExists('bao_cao_thang');
        Schema::dropIfExists('cai_dat');
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['bc_tuan', 'bc_thang']);
        });
    }
};
