<?php

namespace App\Services;

use App\Models\BaoCaoThangDuAn;
use App\Models\BaoCaoTuan;
use App\Models\BaoCaoTuanDuAn;
use App\Models\LoaiHinhDuAn;
use App\Support\KyThang;
use Illuminate\Support\Collection;

/**
 * Bảng báo cáo tháng theo mẫu "Tình hình triển khai quy hoạch điện VIII điều chỉnh…": mỗi loại hình
 * 1 sheet, trong sheet chia nhóm I, II… (kèm tổng công suất), cuối sheet là ghi chú dự kiến vận
 * hành thương mại theo từng năm. Dùng chung cho file Excel và bảng xem trên web.
 */
class XuatExcelBaoCaoThang
{
    /**
     * @param  Collection<BaoCaoThangDuAn>  $dsMuc  các dòng (đã nạp duAn, baoCao)
     * @return array<int, array> mỗi phần tử 1 sheet: ma, ten, tieu_de, cot, nhom[], muc
     */
    public function cauTruc(Collection $dsMuc): array
    {
        $cauHinh = config('duan.bao_cao_thang.sheet');
        $thuTu = array_flip(array_keys($cauHinh));
        $loaiHinh = LoaiHinhDuAn::danhSach();
        $tenNhom = config('duan.bao_cao_thang.nhom');
        $thuTuNhom = array_flip(array_keys($tenNhom));

        return $dsMuc
            ->groupBy(fn (BaoCaoThangDuAn $m) => $m->duAn?->loai_hinh ?? 'khac')
            ->sortBy(fn ($ds, $ma) => [$thuTu[$ma] ?? 99, $loaiHinh->get($ma)?->thu_tu ?? 99])
            ->map(function (Collection $ds, string $ma) use ($cauHinh, $loaiHinh, $tenNhom, $thuTuNhom) {
                $ch = $cauHinh[$ma] ?? [];
                $tenLoai = mb_strtoupper($loaiHinh->get($ma)?->ten ?? $ma);
                $cot = self::cot($ch);

                $nhom = $ds->groupBy(fn (BaoCaoThangDuAn $m) => $m->maNhom())
                    ->sortBy(fn ($x, $k) => $thuTuNhom[$k] ?? 99)
                    ->map(fn (Collection $dsNhom, string $k) => [
                        'ma' => $k,
                        'ten' => $tenNhom[$k] ?? $k,
                        'tong_mw' => $dsNhom->sum(fn (BaoCaoThangDuAn $m) => $m->congSuatMw()),
                        'muc' => $dsNhom->sortBy(fn (BaoCaoThangDuAn $m) => $m->duAn?->ten)->values(),
                    ])->values();

                return [
                    'ma' => $ma,
                    'ten' => $ch['ten'] ?? $tenLoai,
                    'tieu_de' => $ch['tieu_de'] ?? 'CÁC DỰ ÁN '.$tenLoai,
                    'cot' => $cot,
                    'rong' => $ch['rong'] ?? null,
                    'nhom' => $nhom->all(),
                    'muc' => $ds->values(),
                ];
            })
            ->values()
            ->all();
    }

    /** Các cột của sheet theo mẫu: khoá dữ liệu, tiêu đề cột, kiểu ô, độ rộng mặc định */
    public static function cot(array $cauHinhSheet): array
    {
        $cot = [
            ['khoa' => 'tt', 'ten' => 'TT', 'kieu' => 'o_giua', 'rong' => 6],
            ['khoa' => 'ten', 'ten' => 'Tên dự án', 'kieu' => 'o_dam', 'rong' => 18],
            ['khoa' => 'dia_diem', 'ten' => 'Địa điểm xây dựng', 'kieu' => 'o', 'rong' => 15],
            ['khoa' => 'cong_suat', 'ten' => 'Công suất (MW)', 'kieu' => 'o_giua', 'rong' => 10],
            ['khoa' => 'chu_dau_tu', 'ten' => 'Chủ đầu tư', 'kieu' => 'o', 'rong' => 17],
            ['khoa' => 'phap_ly', 'ten' => "Tình trạng pháp lý\nDự án", 'kieu' => 'o', 'rong' => 22],
            ['khoa' => 'tien_do_cod', 'ten' => "Tiến độ vận hành\nCOD theo CTĐT", 'kieu' => 'o', 'rong' => 18],
        ];
        if (! empty($cauHinhSheet['thuc_te'])) {
            $cot[] = ['khoa' => 'tien_do_thuc_te', 'ten' => 'Tiến độ dự kiến đưa vào vận hành thực tế', 'kieu' => 'o', 'rong' => 17];
        }
        $cot[] = ['khoa' => 'tinh_hinh', 'ten' => "Tình hình triển khai thực hiện,\nKhó khăn vướng mắc", 'kieu' => 'o', 'rong' => 50];
        if ($cauHinhSheet['phuong_an'] ?? true) {
            $cot[] = ['khoa' => 'phuong_an', 'ten' => 'Phương án xử lý', 'kieu' => 'o', 'rong' => 22];
        }
        $cot[] = ['khoa' => 'ghi_chu', 'ten' => 'Ghi chú', 'kieu' => 'o', 'rong' => 18];

        return $cot;
    }

    /** Nội dung 1 ô của dòng dự án */
    public static function giaTri(BaoCaoThangDuAn $m, string $khoa, int $stt): string
    {
        return match ($khoa) {
            'tt' => (string) $stt,
            'ten' => $m->duAn?->ten ?? '(dự án đã xoá)',
            // Chưa ghi tiến độ theo chủ trương nhưng có mốc COD dự kiến thì hiện mốc đó cho khỏi trống
            'tien_do_cod' => trim((string) $m->tien_do_cod) !== '' ? trim($m->tien_do_cod)
                : ($m->codHienThi() ? 'Dự kiến COD: '.$m->codHienThi() : ''),
            default => trim((string) $m->{$khoa}),
        };
    }

    public static function tieuDeNhom(array $nhom): string
    {
        return $nhom['ten'].($nhom['tong_mw'] > 0 ? ' - TỔNG CÔNG SUẤT '.BaoCaoTuanDuAn::so($nhom['tong_mw'], 1).' MW' : '');
    }

    /**
     * Ghi chú cuối sheet như mẫu: "Năm 2027: Dự kiến có 14 dự án đi vào vận hành thương mại (COD),
     * với tổng công suất khoảng 589 MW (…)" — gom theo năm COD chủ đầu tư khai; dự án đã vận hành
     * không tính, dự án chưa có năm COD gom thành 1 dòng riêng.
     */
    public static function ghiChuTuDong(Collection $dsMuc): string
    {
        $conLai = $dsMuc->reject(fn (BaoCaoThangDuAn $m) => $m->maNhom() === 'da_van_hanh');
        // Chưa ai khai công suất thì bỏ hẳn cụm "tổng công suất", không ghi "0MW"
        $tongMw = fn (Collection $ds, string $truoc) => ($t = $ds->sum(fn ($m) => $m->congSuatMw())) > 0
            ? $truoc.BaoCaoTuanDuAn::so($t, 1).'MW' : '';
        $ten = fn (BaoCaoThangDuAn $m) => preg_replace('/^(dự án)\s+/iu', '', trim((string) $m->duAn?->ten));
        $mwMuc = fn (BaoCaoThangDuAn $m) => $m->congSuatMw() > 0 ? ' - '.BaoCaoTuanDuAn::so($m->congSuatMw(), 1).'MW' : '';

        $dong = [];
        foreach ($conLai->whereNotNull('cod_nam')->sortBy('cod_nam')->groupBy('cod_nam') as $nam => $ds) {
            $chiTiet = $ds->map(fn ($m) => $ten($m).$mwMuc($m).', '.$m->codHienThi())->implode('; ');
            $dong[] = "Năm {$nam}: Dự kiến có ".sprintf('%02d', $ds->count())
                .' dự án đi vào vận hành thương mại (COD)'.$tongMw($ds, ', với tổng công suất khoảng ')." ({$chiTiet}).";
        }
        $chuaCo = $conLai->whereNull('cod_nam');
        if ($chuaCo->isNotEmpty()) {
            $dong[] = 'Có '.sprintf('%02d', $chuaCo->count()).' dự án chưa dự kiến năm vận hành thương mại'
                .$tongMw($chuaCo, ', với tổng công suất ').' ('.$chuaCo->map(fn ($m) => $ten($m).$mwMuc($m))->implode('; ').').';
        }

        return implode("\n", $dong);
    }

    /**
     * @param  array<string, string>  $ghiChu  ghi chú từng sheet (theo mã loại hình) Sở đã chỉnh — thiếu thì tự tạo
     */
    public function tao(KyThang $ky, Collection $dsMuc, array $ghiChu = [], ?string $donVi = null): TaoTepExcel
    {
        $x = new TaoTepExcel;
        $cauTruc = $this->cauTruc($dsMuc);
        if (! $cauTruc) {
            $s = $x->themSheet('TỔNG HỢP', [120]);
            $x->dong($s, 2, [config('duan.bao_cao_thang.tieu_de')], 'tieu_de');
            $x->dong($s, 4, ['Chưa có báo cáo tháng nào được gửi trong '.mb_strtolower($ky->ten()).'.'], 'nghieng');

            return $x;
        }

        foreach ($cauTruc as $sh) {
            $cot = $sh['cot'];
            $n = count($cot);
            $rong = $sh['rong'] && count($sh['rong']) === $n ? $sh['rong'] : array_column($cot, 'rong');
            $s = $x->themSheet($sh['ten'], $rong, 5);

            $x->dong($s, 2, [config('duan.bao_cao_thang.tieu_de')], 'tieu_de', 18.75);
            $x->gop($s, 2, 1, 2, $n);
            $x->dong($s, 3, [$sh['tieu_de']], 'tieu_de_phu', 17.25);
            $x->gop($s, 3, 1, 3, $n);
            $x->dong($s, 4, ['('.$ky->ten().($donVi ? ' — Đơn vị báo cáo: '.$donVi : '').')'], 'nghieng');
            $x->gop($s, 4, 1, 4, $n);
            $x->dong($s, 5, array_column($cot, 'ten'), 'dau_cot', 48);

            $r = 6;
            $rongNhom = array_sum(array_slice($rong, 1));
            foreach ($sh['nhom'] as $i => $nhom) {
                $tieuDe = self::tieuDeNhom($nhom);
                $x->dong($s, $r, [['v' => BaoCaoTuan::soLaMa($i + 1), 'kieu' => 'nhom_giua'], $tieuDe], 'nhom',
                    max(18, TaoTepExcel::caoUocLuong($tieuDe, $rongNhom)));
                $x->gop($s, $r, 2, $r, $n);
                $r++;
                foreach ($nhom['muc'] as $j => $m) {
                    $x->dong($s, $r++, array_map(fn ($c) => ['v' => self::giaTri($m, $c['khoa'], $j + 1), 'kieu' => $c['kieu']], $cot));
                }
            }

            $noiDung = trim((string) ($ghiChu[$sh['ma']] ?? self::ghiChuTuDong($sh['muc'])));
            if ($noiDung !== '') {
                $r++;
                $x->dong($s, $r++, [null, ['v' => 'Ghi chú:', 'kieu' => 'ghi_dam']], 'thuong');
                $rongGhi = array_sum(array_slice($rong, 2));
                foreach (BaoCaoTuanDuAn::tachDong($noiDung) as $dong) {
                    $x->dong($s, $r, [null, null, ['v' => $dong, 'kieu' => 'ghi']], 'thuong', TaoTepExcel::caoUocLuong($dong, $rongGhi));
                    $x->gop($s, $r, 3, $r, $n);
                    $r++;
                }
            }
        }

        return $x;
    }
}
