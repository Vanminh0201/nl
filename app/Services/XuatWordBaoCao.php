<?php

namespace App\Services;

use App\Models\BaoCaoTuan;
use App\Models\BaoCaoTuanDuAn;
use App\Support\KyBaoCao;
use Illuminate\Support\Collection;

/** Dựng file Word theo mẫu "Báo cáo tình hình triển khai dự án" hằng tuần. */
class XuatWordBaoCao
{
    /** Báo cáo của 1 chủ đầu tư — khổ dọc, bố cục như mẫu. */
    public function baoCao(BaoCaoTuan $bc): TaoTepWord
    {
        $bc->loadMissing(['chiTiet.duAn', 'nguoiBaoCao']);
        $ky = $bc->ky();
        $donVi = $bc->ten_don_vi_hien_thi;
        $w = new TaoTepWord;

        $trai = [];
        if ($bc->co_quan_chu_quan) {
            $trai[] = [mb_strtoupper($bc->co_quan_chu_quan), ['can' => 'center', 'co' => 12]];
        }
        $trai[] = [mb_strtoupper($donVi), ['can' => 'center', 'co' => 12, 'dam' => true]];
        $trai[] = ['', ['vach_tren' => true, 'le_trai' => 2.1, 'le_phai' => 2.1, 'co' => 2, 'truoc' => 3]];
        $trai[] = ['Số: '.$bc->soHieuDeTrong(), ['can' => 'center', 'co' => 13, 'truoc' => 4]];
        $phai = [
            ['CỘNG HOÀ XÃ HỘI CHỦ NGHĨA VIỆT NAM', ['can' => 'center', 'co' => 12, 'dam' => true]],
            ['Độc lập – Tự do – Hạnh phúc', ['can' => 'center', 'co' => 13, 'dam' => true]],
            ['', ['vach_tren' => true, 'le_trai' => 1.4, 'le_phai' => 1.4, 'co' => 2, 'truoc' => 3]],
            [$bc->dongNgayThang(), ['can' => 'center', 'co' => 13, 'nghieng' => true, 'truoc' => 4]],
        ];
        // Đo thực tế Times New Roman 12pt: "TẬP ĐOÀN ĐIỆN LỰC VIỆT NAM" rộng 6,35cm, quốc hiệu in đậm
        // 8,79cm — chia 6,8/9,2 trên 16cm và thu lề ô còn 0,05cm để cả hai dòng không bị xuống hàng.
        $w->bang([[['doan' => $trai], ['doan' => $phai]]], [6.8, 9.2], ['vien' => false, 'le_o' => 0.05]);

        $w->doan('BÁO CÁO', ['can' => 'center', 'dam' => true, 'co' => 14, 'truoc' => 18])
            ->doan("Tình hình triển khai thực hiện các dự án tuần {$ky->tuan} năm {$ky->nam}", ['can' => 'center', 'dam' => true, 'co' => 14])
            ->doan('(Từ ngày '.$ky->tuNgay->format('d/m/Y').' đến ngày '.$ky->denNgay->format('d/m/Y').')', ['can' => 'center', 'nghieng' => true])
            ->doan('', ['vach_tren' => true, 'le_trai' => 6.5, 'le_phai' => 6.5, 'co' => 2, 'truoc' => 3]);

        $this->kinhGui($w, $bc->kinh_gui ?: config('duan.bao_cao.kinh_gui'));

        $than = ['can' => 'both', 'thut_dau' => 1, 'truoc' => 3, 'sau' => 3, 'gian_dong' => 1.1];
        $w->doan("{$donVi} kính báo cáo tình hình triển khai thực hiện các dự án trong tuần {$ky->tuan} năm {$ky->nam} như sau:", $than + ['truoc' => 9]);
        foreach (BaoCaoTuanDuAn::tachDong($bc->gioi_thieu) as $dong) {
            $w->doan($dong, $than);
        }

        foreach ($bc->chiTiet->values() as $i => $ct) {
            $w->doan(BaoCaoTuan::soLaMa($i + 1).'. '.$ct->tieuDeMuc(), $than + ['dam' => true, 'truoc' => 9, 'giu_voi_sau' => true]);
            foreach ($ct->cacMuc($ky->namTaiChinh()) as $muc) {
                $w->doan($muc['tieu_de'], $than + ['dam' => true, 'giu_voi_sau' => true]);
                foreach ($muc['dong'] as $dong) {
                    $w->doan($dong, $than);
                }
            }
        }

        $w->doan("{$donVi} kính báo cáo./.", $than + ['truoc' => 9, 'sau' => 9]);

        $noiNhan = [['Nơi nhận:', ['dam' => true, 'nghieng' => true, 'co' => 12]]];
        foreach (BaoCaoTuanDuAn::tachDong($bc->noi_nhan ?: config('duan.bao_cao.noi_nhan')) as $dong) {
            $noiNhan[] = [$dong, ['co' => 11]];
        }
        $kyTen = [];
        foreach ($bc->dongChucVuKy() as $dong) {
            $kyTen[] = [mb_strtoupper($dong), ['can' => 'center', 'dam' => true]];
        }
        $kyTen[] = ['', ['sau' => 72]];
        $kyTen[] = [$bc->nguoi_ky ?? '', ['can' => 'center', 'dam' => true]];
        $w->bang([[['doan' => $noiNhan], ['doan' => $kyTen]]], [7.5, 8.5], ['vien' => false]);

        return $w;
    }

    /**
     * Bảng tổng hợp của Sở — khổ ngang, 1 hàng/dự án từ các báo cáo đã nộp trong kỳ.
     *
     * @param  Collection<BaoCaoTuan>  $dsBaoCao  báo cáo đã nộp/đã tiếp nhận trong kỳ
     * @param  Collection<string>  $chuaNop  tên các chủ đầu tư chưa gửi báo cáo
     * @param  Collection<string>  $boSung  tên các chủ đầu tư đang bị yêu cầu bổ sung (không đưa số liệu vào bảng)
     */
    public function tongHop(KyBaoCao $ky, Collection $dsBaoCao, Collection $chuaNop, Collection $boSung, int $soPhaiNop): TaoTepWord
    {
        $w = new TaoTepWord(ngang: true, coChu: 12);
        $coQuan = config('duan.bao_cao.don_vi_tong_hop');

        $trai = [
            [mb_strtoupper($coQuan), ['can' => 'center', 'co' => 12, 'dam' => true]],
            ['', ['vach_tren' => true, 'le_trai' => 3.5, 'le_phai' => 3.5, 'co' => 2, 'truoc' => 3]],
        ];
        $phai = [
            ['CỘNG HOÀ XÃ HỘI CHỦ NGHĨA VIỆT NAM', ['can' => 'center', 'co' => 12, 'dam' => true]],
            ['Độc lập – Tự do – Hạnh phúc', ['can' => 'center', 'co' => 13, 'dam' => true]],
            ['', ['vach_tren' => true, 'le_trai' => 4.6, 'le_phai' => 4.6, 'co' => 2, 'truoc' => 3]],
        ];
        $w->bang([[['doan' => $trai], ['doan' => $phai]]], [10, 15.7], ['vien' => false]);

        $w->doan('BẢNG TỔNG HỢP', ['can' => 'center', 'dam' => true, 'co' => 14, 'truoc' => 12])
            ->doan('Tình hình triển khai các dự án theo báo cáo tuần của chủ đầu tư', ['can' => 'center', 'dam' => true, 'co' => 14])
            ->doan("Tuần {$ky->tuan} năm {$ky->nam} (từ ngày ".$ky->tuNgay->format('d/m/Y').' đến ngày '.$ky->denNgay->format('d/m/Y').')',
                ['can' => 'center', 'nghieng' => true, 'sau' => 9]);

        $daTiepNhan = $dsBaoCao->where('trang_thai', 'da_tiep_nhan')->count();
        $w->doan("- Số chủ đầu tư phải báo cáo: {$soPhaiNop}; số báo cáo đưa vào tổng hợp: {$dsBaoCao->count()} (đã tiếp nhận {$daTiepNhan}, chờ tiếp nhận "
            .($dsBaoCao->count() - $daTiepNhan).').', ['can' => 'both', 'sau' => 3]);
        if ($boSung->isNotEmpty()) {
            $w->doan('- Đang yêu cầu bổ sung: '.$boSung->count().' ('.$boSung->implode('; ').').', ['can' => 'both', 'sau' => 3]);
        }
        $w->doan('- Chưa gửi báo cáo: '.($chuaNop->isEmpty() ? 'không có.' : $chuaNop->count().' ('.$chuaNop->implode('; ').').'),
            ['can' => 'both', 'sau' => 6]);

        $nam = $ky->namTaiChinh();
        $tieuDe = ['STT', 'Dự án', 'Chủ đầu tư', "Tiến độ\n(%)", 'Tình hình thực hiện', 'Khó khăn, vướng mắc, kiến nghị',
            'Kế hoạch tiếp theo', "Giải ngân năm {$nam}\n(tỷ đồng)", "Lũy kế giải ngân\n(tỷ đồng)"];
        $hang = [array_map(fn ($t) => ['nen' => 'E7E6E6', 'doan' => array_map(
            fn ($d) => [$d, ['can' => 'center', 'dam' => true, 'co' => 11]],
            explode("\n", $t)
        )], $tieuDe)];

        $stt = 0;
        $tong = ['kh' => null, 'th' => null, 'tm' => null, 'lk' => null];
        $cong = fn (?float $a, ?float $b) => $b === null ? $a : ($a ?? 0) + $b;
        foreach ($dsBaoCao as $bc) {
            foreach ($bc->chiTiet as $ct) {
                $stt++;
                $cot = fn (?string $chu) => ['doan' => array_map(
                    fn ($d) => [$d, ['co' => 11, 'can' => 'both', 'sau' => 3]],
                    BaoCaoTuanDuAn::tachDong($chu) ?: ['']
                )];
                $hang[] = [
                    ['doan' => [[(string) $stt, ['can' => 'center', 'co' => 11]]]],
                    ['doan' => [[$ct->duAn?->ten ?? '(dự án đã xoá)', ['co' => 11, 'dam' => true]]]],
                    ['doan' => [[$bc->ten_don_vi_hien_thi, ['co' => 11]]]],
                    ['doan' => [[BaoCaoTuanDuAn::phanTramTienDo($ct->tien_do), ['can' => 'center', 'co' => 11]]]],
                    $cot($ct->cong_viec_dang_lam),
                    $cot($ct->kho_khan),
                    $cot($ct->ke_hoach),
                    $this->oGiaiNgan('KH', $ct->ke_hoach_von_nam, 'TH', $ct->giai_ngan_nam),
                    $this->oGiaiNgan('TMĐT', $ct->tong_muc_dau_tu, 'LK', $ct->luy_ke_giai_ngan),
                ];
                $tong = ['kh' => $cong($tong['kh'], $ct->ke_hoach_von_nam), 'th' => $cong($tong['th'], $ct->giai_ngan_nam),
                    'tm' => $cong($tong['tm'], $ct->tong_muc_dau_tu), 'lk' => $cong($tong['lk'], $ct->luy_ke_giai_ngan)];
            }
        }
        if ($stt === 0) {
            $hang[] = [['doan' => [['Chưa có báo cáo nào được gửi trong tuần này.', ['nghieng' => true, 'co' => 11]]]], '', '', '', '', '', '', '', ''];
        } elseif ($stt > 1) {
            $hang[] = [
                ['doan' => [['', ['co' => 11]]]],
                ['doan' => [['Tổng cộng', ['co' => 11, 'dam' => true]]]], '', '', '', '', '',
                $this->oGiaiNgan('KH', $tong['kh'], 'TH', $tong['th'], true),
                $this->oGiaiNgan('TMĐT', $tong['tm'], 'LK', $tong['lk'], true),
            ];
        }

        $w->bang($hang, [1.0, 3.4, 2.8, 1.5, 5.2, 4.2, 3.4, 2.1, 2.1], ['so_hang_tieu_de' => 1]);
        $w->doan('Ghi chú: KH - kế hoạch vốn năm; TH - giá trị giải ngân trong năm; TMĐT - tổng mức đầu tư; LK - lũy kế giải ngân từ khởi công; '
            .'tỷ lệ "Đạt" so với KH hoặc TMĐT. Đơn vị: tỷ đồng.', ['nghieng' => true, 'co' => 11, 'truoc' => 6]);

        return $w;
    }

    /** Ô giải ngân ghi rõ từng chỉ tiêu: "KH: 457" / "TH: 43,4" / "Đạt 9,5%" (đơn vị tỷ đồng ghi ở tiêu đề cột). */
    private function oGiaiNgan(string $nhanMau, ?float $mau, string $nhanGiaTri, ?float $giaTri, bool $dam = false): array
    {
        $tc = ['can' => 'left', 'co' => 11] + ($dam ? ['dam' => true] : []);
        $doan = [];
        if ($mau !== null) {
            $doan[] = ["{$nhanMau}: ".BaoCaoTuanDuAn::so($mau), $tc];
        }
        if ($giaTri !== null) {
            $doan[] = ["{$nhanGiaTri}: ".BaoCaoTuanDuAn::so($giaTri), $tc];
        }
        if ($giaTri !== null && $mau) {
            $doan[] = ['Đạt '.BaoCaoTuanDuAn::phanTram($giaTri, $mau).'%', $tc + ['nghieng' => true]];
        }

        return ['doan' => $doan ?: [['', $tc]]];
    }

    private function kinhGui(TaoTepWord $w, string $kinhGui): void
    {
        // Dòng "Kính gửi" cách phần nội dung bên dưới 12pt như văn bản mẫu
        $dong = BaoCaoTuanDuAn::tachDong($kinhGui);
        if (count($dong) <= 1) {
            $w->doan([['chu' => 'Kính gửi: '], ['chu' => $dong[0] ?? '']], ['can' => 'center', 'truoc' => 9, 'sau' => 12]);

            return;
        }
        $w->doan('Kính gửi:', ['le_trai' => 3.2, 'truoc' => 9]);
        foreach ($dong as $i => $d) {
            $w->doan(str_starts_with($d, '-') ? $d : '- '.$d, ['le_trai' => 4.2, 'sau' => $i === count($dong) - 1 ? 12 : 0]);
        }
    }
}
