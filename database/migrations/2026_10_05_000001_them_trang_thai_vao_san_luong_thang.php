<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Sản lượng chủ đầu tư đã gửi phải được Sở tiếp nhận; đã gửi thì khoá, Sở trả lại mới sửa được.
// trang_thai: nhap (đang soạn) → da_gui (chờ tiếp nhận) → da_tiep_nhan; Sở trả lại → tra_lai (chủ đầu tư sửa, gửi lại).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('san_luong_thang', function (Blueprint $t) {
            $t->string('trang_thai', 20)->default('nhap')->after('gui_luc');
            $t->foreignId('nguoi_xu_ly_id')->nullable()->after('trang_thai')->constrained('users')->nullOnDelete();
            $t->timestamp('xu_ly_luc')->nullable()->after('nguoi_xu_ly_id');
            $t->text('y_kien_xu_ly')->nullable()->after('xu_ly_luc');
            $t->index('trang_thai');
        });

        // Số liệu đã có: Sở nhập coi như đã tiếp nhận; chủ đầu tư đã gửi chuyển sang chờ tiếp nhận
        DB::table('san_luong_thang')->whereNotNull('gui_luc')->where('nguon', 'so')->update(['trang_thai' => 'da_tiep_nhan']);
        DB::table('san_luong_thang')->whereNotNull('gui_luc')->where('nguon', '!=', 'so')->update(['trang_thai' => 'da_gui']);
    }

    public function down(): void
    {
        Schema::table('san_luong_thang', function (Blueprint $t) {
            $t->dropConstrainedForeignId('nguoi_xu_ly_id');
            $t->dropIndex(['trang_thai']);
            $t->dropColumn(['trang_thai', 'xu_ly_luc', 'y_kien_xu_ly']);
        });
    }
};
