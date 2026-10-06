<?php

use App\Support\KyBaoCao;
use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Kỳ báo cáo đổi từ thứ Hai–Chủ nhật sang "thứ Năm tuần trước → thứ Năm tuần này" (xem KyBaoCao):
    // tính lại khoảng ngày lưu sẵn của các báo cáo đã có theo số tuần của chúng.
    public function up(): void
    {
        foreach (DB::table('bao_cao_tuan')->get(['id', 'nam', 'tuan']) as $b) {
            $ky = KyBaoCao::tuSo((int) $b->nam, (int) $b->tuan);
            DB::table('bao_cao_tuan')->where('id', $b->id)->update([
                'tu_ngay' => $ky->tuNgay->toDateString(),
                'den_ngay' => $ky->denNgay->toDateString(),
            ]);
        }
    }

    public function down(): void
    {
        foreach (DB::table('bao_cao_tuan')->get(['id', 'nam', 'tuan']) as $b) {
            $thu2 = CarbonImmutable::now(KyBaoCao::MUI_GIO)->setISODate((int) $b->nam, (int) $b->tuan, 1);
            DB::table('bao_cao_tuan')->where('id', $b->id)->update([
                'tu_ngay' => $thu2->toDateString(),
                'den_ngay' => $thu2->addDays(6)->toDateString(),
            ]);
        }
    }
};
