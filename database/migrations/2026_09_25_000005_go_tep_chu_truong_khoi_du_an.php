<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('du_an', function (Blueprint $table) {
            $table->dropColumn('tep_chu_truong');
        });
    }

    public function down(): void
    {
        Schema::table('du_an', function (Blueprint $table) {
            $table->string('tep_chu_truong')->nullable()->after('ngay_van_ban');
        });
    }
};
