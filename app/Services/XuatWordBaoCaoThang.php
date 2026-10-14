<?php

namespace App\Services;

use App\Models\BaoCaoTuan;
use App\Support\KyThang;

/**
 * File Word báo cáo tháng theo mẫu "Tình hình triển khai các dự án năng lượng trên địa bàn tỉnh" (khổ ngang): bảng theo
 * xã, rồi phần "Thống kê các dự án năng lượng trên địa bàn các xã" gồm thống kê theo loại hình và danh sách dự án trên
 * địa bàn từ 2 xã trở lên. Bảng dựng bởi TongHopBaoCaoThang.
 */
class XuatWordBaoCaoThang
{
    /** Nền ô như mẫu: hàng tiêu đề cột, hàng xã và hàng tình trạng */
    private const NEN_TIEU_DE = 'E7E6E6';

    private const NEN_NHOM = 'F2F2F2';

    public function tao(KyThang $ky, array $bang, ?string $donVi = null): TaoTepWord
    {
        $w = new TaoTepWord(ngang: true, coChu: 12);
        $giua = ['can' => 'center'];
        $dau = ['can' => 'center', 'dam' => true];
        $o = fn (string $chu, array $tc = []) => ['doan' => array_map(fn ($dong) => [$dong, $tc], preg_split('/\r\n|\r|\n/', $chu))];
        $tieuDe = fn (string $chu) => $o($chu, $dau) + ['nen' => self::NEN_TIEU_DE, 'giua_doc' => true];
        // Bảng chính chữ 10,5 như mẫu; mọi ô canh giữa theo chiều dọc
        $nho = fn (string $chu, array $tc = []) => $o($chu, $tc + ['co' => 10.5, 'sau' => 2]) + ['giua_doc' => true];
        $tieuDeNho = fn (string $chu, array $them = []) => $nho($chu, $dau) + $them + ['nen' => self::NEN_TIEU_DE];
        $nhom = fn (string $chu) => $nho($chu, ['dam' => true, 'giu_voi_sau' => true]) + ['gop' => 8, 'nen' => self::NEN_NHOM];

        $w->doan(XuatExcelBaoCaoThang::TIEU_DE, ['can' => 'center', 'dam' => true, 'co' => 14])
            ->doan('('.$ky->ten().($donVi ? ' — Đơn vị báo cáo: '.$donVi : '').')', ['can' => 'center', 'nghieng' => true, 'sau' => 8]);

        $hang = [
            [
                $tieuDeNho('TT', ['gop_doc' => 'dau']),
                ['doan' => [['Thông tin dự án', $dau + ['co' => 10.5]], ['(Tên, chủ đầu tư, công suất)', $dau + ['co' => 10.5]]],
                    'gop_doc' => 'dau', 'giua_doc' => true, 'nen' => self::NEN_TIEU_DE],
                $tieuDeNho('Dự kiến khởi công', ['gop_doc' => 'dau']),
                $tieuDeNho('Dự kiến hoàn thành', ['gop_doc' => 'dau']),
                $tieuDeNho('Tình hình thực hiện', ['gop_doc' => 'dau']),
                $tieuDeNho('Khó khăn vướng mắc', ['gop_doc' => 'dau']),
                $tieuDeNho('Kiến nghị xử lý', ['gop' => 2]),
            ],
            [
                ...array_fill(0, 6, ['gop_doc' => 'tiep', 'nen' => self::NEN_TIEU_DE]),
                $tieuDeNho('Xã/phường'), $tieuDeNho('Sở, ngành'),
            ],
        ];
        foreach ($bang['xa'] as $xa) {
            $hang[] = [$nhom($xa['ten'])];
            foreach ($xa['nhom'] as $n) {
                $hang[] = [$nhom('* '.$n['ten'])];
                foreach ($n['muc'] as $m) {
                    $dong = [$nho((string) $m['tt'], $giua), $nho(XuatExcelBaoCaoThang::thongTin($m))];
                    foreach (XuatExcelBaoCaoThang::COT_NOI_DUNG as $cot) {
                        $dong[] = $nho((string) ($m['noi_dung'][$cot] ?? ''), ['can' => 'both']);
                    }
                    $hang[] = $dong;
                }
            }
        }
        if (! $bang['xa']) {
            $hang[] = [$nho('Chưa có dự án nào.', ['nghieng' => true]) + ['gop' => 8]];
        }
        // Tỉ lệ độ rộng cột theo mẫu
        $w->bang($hang, [0.9, 4.2, 2.6, 2.1, 4.9, 3.6, 3.2, 4.2], ['so_hang_tieu_de' => 2]);

        $w->doan(XuatExcelBaoCaoThang::TIEU_DE_THONG_KE, ['can' => 'center', 'dam' => true, 'truoc' => 18, 'sau' => 6, 'giu_voi_sau' => true])
            ->doan('1. Thống kê theo loại hình', ['dam' => true, 'sau' => 4, 'giu_voi_sau' => true]);
        $chuaRo = XuatExcelBaoCaoThang::coCotChuaRo($bang);
        $hang = [array_map($tieuDe, array_merge(['STT', 'Loại hình', 'Trong 1 xã', 'Từ 2 xã trở lên'], $chuaRo ? ['Chưa xác định xã'] : []))];
        foreach ($bang['thong_ke']['hang'] as $i => $h) {
            $hang[] = array_merge([$o((string) ($i + 1), $giua), $o($h['loai_hinh']), $o((string) $h['mot_xa'], $giua), $o((string) $h['nhieu_xa'], $giua)],
                $chuaRo ? [$o((string) $h['chua_ro'], $giua)] : []);
        }
        $tong = $bang['thong_ke']['tong'];
        $hang[] = array_merge([$o(''), $o('Tổng cộng', ['dam' => true]), $o((string) $tong['mot_xa'], $dau), $o((string) $tong['nhieu_xa'], $dau)],
            $chuaRo ? [$o((string) $tong['chua_ro'], $dau)] : []);
        $w->bang($hang, $chuaRo ? [1.5, 9.0, 3.5, 3.5, 3.5] : [1.5, 10.0, 4.0, 4.0], ['so_hang_tieu_de' => 1]);

        $w->doan('2. Danh sách dự án trên địa bàn từ 2 xã trở lên', ['dam' => true, 'truoc' => 10, 'sau' => 4, 'giu_voi_sau' => true]);
        $hang = [[$tieuDe('STT'), $tieuDe('Tên dự án'), $tieuDe('Các xã, phường')]];
        foreach ($bang['nhieu_xa'] as $i => $n) {
            $hang[] = [$o(BaoCaoTuan::soLaMa($i + 1).'. '.$n['ten'], ['dam' => true, 'giu_voi_sau' => true]) + ['gop' => 3, 'nen' => self::NEN_NHOM]];
            foreach ($n['muc'] as $m) {
                $hang[] = [$o((string) $m['stt'], $giua), $o($m['ten']), $o($m['xa'])];
            }
        }
        if (! $bang['nhieu_xa']) {
            $hang[] = [$o('Không có dự án nào trên địa bàn từ 2 xã trở lên.', ['nghieng' => true]) + ['gop' => 3]];
        }
        $w->bang($hang, [1.5, 10.0, 14.2], ['so_hang_tieu_de' => 1]);

        return $w;
    }
}
