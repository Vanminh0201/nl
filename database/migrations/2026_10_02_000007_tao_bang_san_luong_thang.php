<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Sản lượng điện từng tháng của dự án đã vận hành: chủ đầu tư báo cáo hoặc Sở nhập thay.
// Mỗi dự án mỗi tháng 1 dòng; users.bc_san_luong = Sở có yêu cầu tài khoản báo cáo sản lượng (null = có).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('san_luong_thang', function (Blueprint $t) {
            $t->id();
            $t->foreignId('du_an_id')->constrained('du_an')->cascadeOnDelete();
            $t->unsignedSmallInteger('nam');
            $t->unsignedTinyInteger('thang');
            $t->decimal('san_luong', 16, 1)->nullable()->comment('kWh');
            $t->text('ghi_chu')->nullable();
            $t->string('nguon', 20)->default('chu_dau_tu');
            $t->foreignId('nguoi_cap_nhat_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('gui_luc')->nullable();
            $t->timestamps();
            $t->unique(['du_an_id', 'nam', 'thang']);
            $t->index(['nam', 'thang']);
        });
        Schema::table('users', function (Blueprint $t) {
            $t->boolean('bc_san_luong')->nullable()->after('bc_thang');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn('bc_san_luong');
        });
        Schema::dropIfExists('san_luong_thang');
    }
};
