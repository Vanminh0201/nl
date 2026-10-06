<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('du_an', function (Blueprint $table) {
            $table->string('tien_do_van_hanh')->nullable()->after('ngay_hoan_thanh');
        });
    }

    public function down(): void
    {
        Schema::table('du_an', function (Blueprint $table) {
            $table->dropColumn('tien_do_van_hanh');
        });
    }
};
