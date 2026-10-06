<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lop_ha_tang', function (Blueprint $t) {
            $t->string('loai_hinh_hoc', 10)->nullable()->after('ten_file')
                ->comment('diem | duong | vung — ghi khi nạp lớp tự tách theo dạng hình học, trống nếu là lớp cũ trộn lẫn nhiều dạng');
            $t->string('mau', 7)->nullable()->after('loai_hinh_hoc')
                ->comment('Mã màu hex tuỳ chỉnh cho lớp, ghi đè màu tự động theo cấp điện áp khi có');
            $t->string('bieu_tuong')->nullable()->after('mau')
                ->comment('Mã biểu tượng (bieu_tuong.ma), chỉ có ý nghĩa với lớp dạng điểm');
            $t->unsignedTinyInteger('kich_thuoc')->nullable()->after('bieu_tuong')
                ->comment('px — bán kính/kích thước icon nếu lớp dạng điểm, độ dày nét nếu lớp dạng đường');
        });
    }

    public function down(): void
    {
        Schema::table('lop_ha_tang', function (Blueprint $t) {
            $t->dropColumn(['loai_hinh_hoc', 'mau', 'bieu_tuong', 'kich_thuoc']);
        });
    }
};
