<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('du_an_cho_duyet')->nullable()->after('da_duyet');
        });

        DB::table('users')
            ->where('vai_tro', 'chu_dau_tu')
            ->where('da_duyet', false)
            ->orderBy('id')
            ->get(['id'])
            ->each(function (object $taiKhoan): void {
                $duAnIds = DB::table('du_an')
                    ->where('chu_dau_tu_id', $taiKhoan->id)
                    ->pluck('id');

                if ($duAnIds->isEmpty()) {
                    return;
                }

                DB::table('users')->where('id', $taiKhoan->id)->update([
                    'du_an_cho_duyet' => json_encode($duAnIds->all(), JSON_THROW_ON_ERROR),
                ]);

                DB::table('du_an')->where('chu_dau_tu_id', $taiKhoan->id)->update([
                    'chu_dau_tu_id' => null,
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('du_an_cho_duyet');
        });
    }
};
