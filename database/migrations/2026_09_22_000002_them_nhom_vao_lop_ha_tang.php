<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lop_ha_tang', function (Blueprint $t) {
            $t->string('nhom')->nullable()->after('ten')
                ->comment('Tên gốc trước khi tách theo dạng hình học — các lớp cùng nhóm gộp chung 1 dòng cha, bấm để mở/thu gọn ở trang Lớp hạ tầng');
        });
    }

    public function down(): void
    {
        Schema::table('lop_ha_tang', function (Blueprint $t) {
            $t->dropColumn('nhom');
        });
    }
};
