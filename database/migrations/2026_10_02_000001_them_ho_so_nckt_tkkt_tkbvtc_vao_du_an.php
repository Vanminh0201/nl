<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Bổ sung 3 hồ sơ pháp lý: báo cáo nghiên cứu khả thi, phê duyệt thiết kế kỹ thuật (TKKT),
// phê duyệt thiết kế bản vẽ thi công (TKBVTC)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('du_an', function (Blueprint $t) {
            $t->boolean('pl_nckt')->default(false)->after('pl_moi_truong');
            $t->boolean('pl_tkkt')->default(false)->after('pl_nckt');
            $t->boolean('pl_tkbvtc')->default(false)->after('pl_pccc');
        });
    }

    public function down(): void
    {
        Schema::table('du_an', function (Blueprint $t) {
            $t->dropColumn(['pl_nckt', 'pl_tkkt', 'pl_tkbvtc']);
        });
    }
};
