<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('du_an_hinh_hoc', function (Blueprint $t) {
            $t->string('bieu_tuong', 20)->nullable()->after('loai')
                ->comment("chỉ có ý nghĩa khi loai='diem', mặc định 'diem_moc' nếu để trống");
        });
    }

    public function down(): void
    {
        Schema::table('du_an_hinh_hoc', function (Blueprint $t) {
            $t->dropColumn('bieu_tuong');
        });
    }
};
