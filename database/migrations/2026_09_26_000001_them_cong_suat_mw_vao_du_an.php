<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('du_an', function (Blueprint $table) {
            $table->decimal('cong_suat_mw', 10, 2)->nullable()->after('cong_suat');
        });

        // Boc tach so cong suat co san tu cot chu tu do (cong_suat), giu nguyen quy uoc so
        // Viet Nam (dau cham la phan cach nghin, dau phay la phan cach thap phan).
        foreach (DB::table('du_an')->whereNotNull('cong_suat')->get(['id', 'cong_suat']) as $d) {
            if (! preg_match('/\d[\d.,]*/', $d->cong_suat, $kq)) {
                continue;
            }
            $so = $kq[0];
            $so = str_contains($so, ',')
                ? str_replace(',', '.', str_replace('.', '', $so))
                : str_replace('.', '', $so);

            DB::table('du_an')->where('id', $d->id)->update(['cong_suat_mw' => (float) $so]);
        }
    }

    public function down(): void
    {
        Schema::table('du_an', function (Blueprint $table) {
            $table->dropColumn('cong_suat_mw');
        });
    }
};
