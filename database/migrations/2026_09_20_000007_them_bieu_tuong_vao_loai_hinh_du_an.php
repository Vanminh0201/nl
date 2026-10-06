<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loai_hinh_du_an', function (Blueprint $t) {
            $t->string('bieu_tuong', 20)->default('khac')->after('mau');
        });

        // Loại hình có sẵn: gán icon trùng mã, giữ nguyên hình ảnh đang hiển thị trên bản đồ
        // (trước đây icon được chọn cứng theo mã trong public/js/bieu-tuong.js).
        DB::table('loai_hinh_du_an')->get()->each(function ($l) {
            DB::table('loai_hinh_du_an')->where('id', $l->id)->update([
                'bieu_tuong' => in_array($l->ma, ['ccn', 'kcn', 'che_bien', 'vlxd', 'khoang_san', 'thuy_dien', 'dien_gio', 'dien_mat_troi', 'det_may'], true)
                    ? $l->ma
                    : 'khac',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('loai_hinh_du_an', function (Blueprint $t) {
            $t->dropColumn('bieu_tuong');
        });
    }
};
