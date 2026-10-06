<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('du_an_hinh_hoc', function (Blueprint $t) {
            $t->json('nhan_diem')->nullable()->after('toa_do')
                ->comment('Nhãn riêng từng điểm khi loai=diem có nhiều hơn 1 toạ độ (nhóm điểm), cùng thứ tự với toa_do');
        });
    }

    public function down(): void
    {
        Schema::table('du_an_hinh_hoc', function (Blueprint $t) {
            $t->dropColumn('nhan_diem');
        });
    }
};
