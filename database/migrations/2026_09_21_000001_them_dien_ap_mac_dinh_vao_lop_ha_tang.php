<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lop_ha_tang', function (Blueprint $t) {
            $t->unsignedSmallInteger('dien_ap_mac_dinh')->nullable()->after('nguon')
                ->comment('kV, áp cho đối tượng không đọc được voltage từ file (VD file khảo sát riêng, không phải OpenStreetMap)');
        });
    }

    public function down(): void
    {
        Schema::table('lop_ha_tang', function (Blueprint $t) {
            $t->dropColumn('dien_ap_mac_dinh');
        });
    }
};
