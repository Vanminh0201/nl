<?php

namespace App\Services;

use App\Models\BaoCaoThang;
use App\Models\BaoCaoThangDuAn;
use App\Models\BaoCaoThangTongHop;
use App\Models\BaoCaoTuan;
use App\Models\DuAn;
use App\Models\LoaiHinhDuAn;
use App\Support\BaoCaoSanLuong;
use App\Support\KyThang;
use Collator;
use Illuminate\Support\Collection;

/**
 * Bảng tổng hợp báo cáo tháng theo mẫu "Tình hình triển khai các dự án năng lượng trên địa bàn tỉnh": dự án nhóm theo
 * xã/phường hiện nay (dự án trên nhiều xã ghi lại ở mỗi xã, số thứ tự đánh lại trong từng xã), trong xã chia theo tình
 * trạng triển khai; kèm thống kê theo loại hình và danh sách dự án trên địa bàn từ 2 xã trở lên. Dùng chung cho trang
 * quản lý, trang in, file Excel và Word.
 */
class TongHopBaoCaoThang
{
    public const TINH_TRANG = [
        'hoan_thanh' => 'Đã hoàn thành, vận hành thương mại',
        'dang_trien_khai' => 'Đang triển khai',
        'chua_trien_khai' => 'Chưa triển khai',
    ];

    public const NHOM_CHUA_RO_XA = 'TRÊN ĐỊA BÀN NHIỀU XÃ, CHƯA XÁC ĐỊNH XÃ CỤ THỂ';

    /** Như mẫu: dự án đã chấp thuận chủ trương đầu tư (đã có nhà đầu tư) tính là đang triển khai */
    public static function tinhTrang(?string $trangThaiDuAn): string
    {
        return match ($trangThaiDuAn) {
            BaoCaoSanLuong::TRANG_THAI_VAN_HANH => 'hoan_thanh',
            'Đang thi công', 'Đã chấp thuận chủ trương đầu tư' => 'dang_trien_khai',
            default => 'chua_trien_khai',
        };
    }

    /**
     * Bảng tổng hợp của Sở cho tháng $ky: mọi dự án nguồn điện, cộng dự án khác đã có nội dung báo cáo tháng.
     *
     * @return array{xa: array, thong_ke: array, nhieu_xa: array, so_du_an: int}
     */
    public function cuaKy(KyThang $ky): array
    {
        $noiDung = $this->noiDung($ky);
        $dsDuAn = DuAn::query()->withoutGlobalScope('chu_dau_tu')
            ->where(fn ($q) => $q->whereIn('loai_hinh', BaoCaoSanLuong::loaiHinh())->orWhereIn('id', $noiDung->keys()))
            ->get(['id', 'ten', 'loai_hinh', 'trang_thai', 'chu_dau_tu', 'cong_suat', 'cong_suat_mw', 'dia_diem_hien_nay']);

        return $this->dung($dsDuAn->map(fn (DuAn $d) => $this->muc($d, $noiDung->get($d->id))));
    }

    /** Bảng theo mẫu cho 1 báo cáo của chủ đầu tư (chỉ các dự án trong báo cáo, nội dung đúng như đã khai). */
    public function cuaBaoCao(BaoCaoThang $baoCao): array
    {
        return $this->dung($baoCao->chiTiet->filter(fn (BaoCaoThangDuAn $m) => $m->duAn)->map(fn (BaoCaoThangDuAn $m) => $this->muc($m->duAn, [
            'noi_dung' => $m->noiDungMau(), 'nguon' => 'chu_dau_tu', 'ky' => $baoCao->ky(), 'bao_cao' => $baoCao,
            'chu_dau_tu' => BaoCaoThangDuAn::chu($m->chu_dau_tu), 'cong_suat' => BaoCaoThangDuAn::chu($m->cong_suat),
        ])));
    }

    /**
     * Nội dung từng dự án trong tháng $ky, theo thứ tự ưu tiên: Sở biên tập tháng này > báo cáo chủ đầu tư gửi tháng
     * này > nội dung gần nhất các tháng trước (cùng tháng thì Sở biên tập trước). Báo cáo đang bị trả bổ sung không lấy.
     *
     * @return Collection<int, array{noi_dung: array, nguon: string, ky: KyThang, bao_cao: ?BaoCaoThang, chu_dau_tu: ?string, cong_suat: ?string, so_sua: ?BaoCaoThangTongHop}>
     */
    public function noiDung(KyThang $ky): Collection
    {
        $denKy = fn ($q, string $tien = '') => $q->where($tien.'nam', '<', $ky->nam)
            ->orWhere(fn ($q) => $q->where($tien.'nam', $ky->nam)->where($tien.'thang', '<=', $ky->thang));
        $thuTu = fn (int $nam, int $thang) => $nam * 12 + $thang;

        $soSua = BaoCaoThangTongHop::with('nguoiSua:id,name')->where(fn ($q) => $denKy($q))->get()
            ->sortByDesc(fn (BaoCaoThangTongHop $s) => $thuTu($s->nam, $s->thang))->unique('du_an_id')->keyBy('du_an_id');
        $baoCao = BaoCaoThang::with('nguoiBaoCao:id,name,don_vi')->daGui()->whereIn('trang_thai', ['da_nop', 'da_tiep_nhan'])
            ->where(fn ($q) => $denKy($q))->get()->keyBy('id');
        $chuDauTu = BaoCaoThangDuAn::whereIn('bao_cao_thang_id', $baoCao->keys())->get()
            ->sortByDesc(fn (BaoCaoThangDuAn $m) => $thuTu($baoCao[$m->bao_cao_thang_id]->nam, $baoCao[$m->bao_cao_thang_id]->thang))
            ->unique('du_an_id')->keyBy('du_an_id');

        return $soSua->keys()->merge($chuDauTu->keys())->unique()->mapWithKeys(function (int $id) use ($soSua, $chuDauTu, $baoCao, $thuTu) {
            $s = $soSua->get($id);
            $m = $chuDauTu->get($id);
            $b = $m ? $baoCao[$m->bao_cao_thang_id] : null;
            $dungSo = $s && (! $b || $thuTu($s->nam, $s->thang) >= $thuTu($b->nam, $b->thang));

            return [$id => [
                'noi_dung' => $dungSo ? $s->noiDungMau() : $m->noiDungMau(),
                'nguon' => $dungSo ? 'so' : 'chu_dau_tu',
                'ky' => $dungSo ? $s->ky() : $b->ky(),
                'bao_cao' => $b,
                'chu_dau_tu' => BaoCaoThangDuAn::chu($m?->chu_dau_tu),
                'cong_suat' => BaoCaoThangDuAn::chu($m?->cong_suat),
                'so_sua' => $dungSo ? $s : null,
            ]];
        });
    }

    /** 1 dòng của bảng: thông tin dự án + nội dung (nếu có) */
    private function muc(DuAn $d, ?array $nd): array
    {
        $congSuat = $d->cong_suat_mw > 0 ? 'Công suất: '.self::soMw((float) $d->cong_suat_mw).' MW'
            : (($cs = $nd['cong_suat'] ?? BaoCaoThangDuAn::chu($d->cong_suat)) ? 'Công suất: '.$cs : null);

        return [
            'du_an' => $d,
            'ten' => $d->ten,
            'chu_dau_tu' => $nd['chu_dau_tu'] ?? BaoCaoThangDuAn::chu($d->chu_dau_tu),
            'cong_suat' => $congSuat,
            'noi_dung' => $nd['noi_dung'] ?? array_fill_keys(array_keys(BaoCaoThangDuAn::NOI_DUNG_MAU), null),
            'nguon' => $nd['nguon'] ?? null,
            'ky_nguon' => $nd['ky'] ?? null,
            'bao_cao' => $nd['bao_cao'] ?? null,
            'so_sua' => $nd['so_sua'] ?? null,
            'tinh_trang' => self::tinhTrang($d->trang_thai),
            'xa' => array_values(array_filter((array) ($d->dia_diem_hien_nay ?? []))),
        ];
    }

    /** Dựng bảng theo xã + 2 bảng thống kê từ danh sách dòng */
    private function dung(Collection $dsMuc): array
    {
        $soSanh = self::soSanhTen();
        $nhomXa = [];
        foreach ($dsMuc as $m) {
            foreach ($m['xa'] ?: [null] as $xa) {
                $nhomXa[$xa ?? ''][] = $m;
            }
        }
        $chuaRo = $nhomXa[''] ?? [];
        unset($nhomXa['']);
        // Xã nhiều dự án trước; bằng nhau thì theo tên
        uksort($nhomXa, fn ($a, $b) => (count($nhomXa[$b]) <=> count($nhomXa[$a])) ?: $soSanh($a, $b));

        $xa = [];
        foreach ($nhomXa as $tenXa => $ds) {
            $xa[] = ['ten' => BaoCaoTuan::soLaMa(count($xa) + 1).'. '.mb_strtoupper($tenXa).' ('.count($ds).' dự án)', 'xa' => $tenXa] + $this->theoTinhTrang($ds, $soSanh);
        }
        if ($chuaRo) {
            $xa[] = ['ten' => self::NHOM_CHUA_RO_XA.' ('.count($chuaRo).' dự án)', 'xa' => null] + $this->theoTinhTrang($chuaRo, $soSanh);
        }

        // Như mẫu: danh sách dự án trên nhiều xã theo thứ tự dự án xuất hiện lần đầu trong bảng, các xã của mỗi dự án theo thứ tự xã trong bảng
        $xuatHien = [];
        foreach ($xa as $n) {
            foreach ($n['nhom'] as $nhom) {
                foreach ($nhom['muc'] as $m) {
                    $xuatHien[$m['du_an']->id] ??= count($xuatHien);
                }
            }
        }

        return [
            'xa' => $xa,
            'thong_ke' => $this->thongKe($dsMuc),
            'nhieu_xa' => $this->nhieuXa($dsMuc, array_flip(array_keys($nhomXa)), $xuatHien),
            'so_du_an' => $dsMuc->count(),
        ];
    }

    /** Các dự án của 1 xã chia theo tình trạng; số thứ tự liên tục trong xã */
    private function theoTinhTrang(array $ds, callable $soSanh): array
    {
        $tt = 0;
        $nhom = [];
        foreach (self::TINH_TRANG as $ma => $ten) {
            $cua = array_values(array_filter($ds, fn ($m) => $m['tinh_trang'] === $ma));
            usort($cua, fn ($a, $b) => $soSanh($a['ten'], $b['ten']));
            if ($cua) {
                $nhom[] = ['ma' => $ma, 'ten' => $ten, 'muc' => array_map(function ($m) use (&$tt) {
                    return $m + ['tt' => ++$tt];
                }, $cua)];
            }
        }

        return ['so_du_an' => count($ds), 'nhom' => $nhom];
    }

    /** Thống kê theo loại hình: số dự án trong 1 xã / từ 2 xã trở lên (/ chưa xác định xã nếu có) */
    private function thongKe(Collection $dsMuc): array
    {
        $loaiHinh = LoaiHinhDuAn::danhSach();
        $hang = $this->theoLoaiHinh($dsMuc)
            ->map(fn ($ds, $ma) => [
                'loai_hinh' => $loaiHinh->get($ma)?->ten ?? $ma,
                'mot_xa' => $ds->filter(fn ($m) => count($m['xa']) === 1)->count(),
                'nhieu_xa' => $ds->filter(fn ($m) => count($m['xa']) >= 2)->count(),
                'chua_ro' => $ds->filter(fn ($m) => count($m['xa']) === 0)->count(),
            ])->values();

        return [
            'hang' => $hang->all(),
            'tong' => ['mot_xa' => $hang->sum('mot_xa'), 'nhieu_xa' => $hang->sum('nhieu_xa'), 'chua_ro' => $hang->sum('chua_ro')],
        ];
    }

    /**
     * Danh sách dự án trên địa bàn từ 2 xã trở lên, nhóm theo loại hình (STT liên tục).
     *
     * @param  array<string, int>  $hangXa  thứ tự các xã trong bảng
     * @param  array<int, int>  $xuatHien  thứ tự xuất hiện lần đầu của từng dự án trong bảng
     */
    private function nhieuXa(Collection $dsMuc, array $hangXa, array $xuatHien): array
    {
        $loaiHinh = LoaiHinhDuAn::danhSach();
        $stt = 0;

        return $this->theoLoaiHinh($dsMuc->filter(fn ($m) => count($m['xa']) >= 2))
            ->map(function ($ds, $ma) use ($loaiHinh, $hangXa, $xuatHien, &$stt) {
                return [
                    'ten' => mb_strtoupper($loaiHinh->get($ma)?->ten ?? $ma).' - '.$ds->count().' DỰ ÁN',
                    'muc' => $ds->sortBy(fn ($m) => $xuatHien[$m['du_an']->id] ?? PHP_INT_MAX)->values()
                        ->map(function ($m) use (&$stt, $hangXa) {
                            $xa = $m['xa'];
                            usort($xa, fn ($a, $b) => ($hangXa[$a] ?? PHP_INT_MAX) <=> ($hangXa[$b] ?? PHP_INT_MAX));

                            return ['stt' => ++$stt, 'ten' => $m['ten'], 'xa' => implode(', ', array_map(fn ($x) => preg_replace('/^Xã\s+/u', '', $x), $xa))];
                        })->all(),
                ];
            })->values()->all();
    }

    /** Các dòng nhóm theo loại hình, loại hình nhiều dự án trước như mẫu (bằng nhau thì theo thứ tự danh mục loại hình) */
    private function theoLoaiHinh(Collection $dsMuc): Collection
    {
        $thuTu = LoaiHinhDuAn::danhSach()->keys()->flip();
        $nhom = $dsMuc->groupBy(fn ($m) => $m['du_an']->loai_hinh)->all();
        uksort($nhom, fn ($a, $b) => (count($nhom[$b]) <=> count($nhom[$a])) ?: (($thuTu[$a] ?? 99) <=> ($thuTu[$b] ?? 99)));

        return collect($nhom);
    }

    /** "29,4" · "30" · "1.403" — số MW gọn như trong văn bản */
    public static function soMw(float $mw): string
    {
        return rtrim(rtrim(number_format($mw, 2, ',', '.'), '0'), ',');
    }

    /** So sánh tên theo thứ tự chữ cái tiếng Việt (Đ sau D, Ư sau U…) */
    private static function soSanhTen(): callable
    {
        $collator = class_exists(Collator::class) ? new Collator('vi_VN') : null;

        return $collator
            ? fn ($a, $b) => $collator->compare((string) $a, (string) $b)
            : fn ($a, $b) => strcmp(mb_strtolower((string) $a), mb_strtolower((string) $b));
    }
}
