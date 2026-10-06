<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('du_an', function (Blueprint $t) {
            $t->foreignId('chu_dau_tu_id')->nullable()->after('chu_dau_tu')
                ->comment('tài khoản vai trò chu_dau_tu được xem riêng dự án này')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('du_an', function (Blueprint $t) {
            $t->dropConstrainedForeignId('chu_dau_tu_id');
        });
    }
};
