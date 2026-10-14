<?php

use App\Support\DiaDiemQuangTri;
use App\Support\DiaDiemQuangTriTruocSapNhap;
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
        Schema::table('du_an', function (Blueprint $table): void {
            $table->json('dia_diem_hien_nay')->nullable()->after('dia_diem');
            $table->json('dia_diem_truoc_day')->nullable()->after('dia_diem_hien_nay');
        });

        DB::table('du_an')
            ->select(['id', 'dia_diem'])
            ->orderBy('id')
            ->chunkById(500, function ($duAns): void {
                foreach ($duAns as $duAn) {
                    $diaDiemCu = trim((string) $duAn->dia_diem);
                    $diaDiemHienNay = $diaDiemCu !== '' ? DiaDiemQuangTri::chuanHoa($diaDiemCu) : null;
                    $diaDiemTruocDay = $diaDiemCu !== ''
                        ? (DiaDiemQuangTriTruocSapNhap::chuanHoa($diaDiemCu) ?? ($diaDiemHienNay ? null : $diaDiemCu))
                        : null;
                    $diaDiems = $diaDiemHienNay ? [$diaDiemHienNay] : [];
                    $diaDiemTruocDays = $diaDiemTruocDay ? [$diaDiemTruocDay] : [];

                    DB::table('du_an')->where('id', $duAn->id)->update([
                        'dia_diem_hien_nay' => json_encode($diaDiems, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                        'dia_diem_truoc_day' => json_encode($diaDiemTruocDays, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                        'dia_diem' => $diaDiems[0] ?? null,
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('du_an', function (Blueprint $table): void {
            $table->dropColumn(['dia_diem_hien_nay', 'dia_diem_truoc_day']);
        });
    }
};
