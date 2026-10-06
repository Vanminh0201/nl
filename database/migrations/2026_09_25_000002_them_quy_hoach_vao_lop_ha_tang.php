<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lop_ha_tang', function (Blueprint $t) {
            $t->boolean('quy_hoach')->default(false)->after('kich_thuoc')
                ->comment('Lớp thể hiện hạ tầng quy hoạch (chưa xây) — vẽ nét đứt, mờ hơn để phân biệt với hiện trạng');
        });
    }

    public function down(): void
    {
        Schema::table('lop_ha_tang', function (Blueprint $t) {
            $t->dropColumn('quy_hoach');
        });
    }
};
