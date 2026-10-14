<?php

namespace App\Services;

use App\Models\BaoCaoTuan;
use App\Support\KyThang;

/**
 * File Excel báo cáo tháng theo mẫu "Tình hình triển khai các dự án năng lượng trên địa bàn tỉnh" (bảng dựng bởi
 * TongHopBaoCaoThang): sheet bảng theo xã, sheet thống kê theo loại hình, sheet dự án trên địa bàn từ 2 xã trở lên.
 */
class XuatExcelBaoCaoThang
{
    public const TIEU_DE = 'TÌNH HÌNH TRIỂN KHAI CÁC DỰ ÁN NĂNG LƯỢNG TRÊN ĐỊA BÀN TỈNH';

    public const TIEU_DE_THONG_KE = 'THỐNG KÊ CÁC DỰ ÁN NĂNG LƯỢNG TRÊN ĐỊA BÀN CÁC XÃ';

    /** Cột nội dung sau "TT" và "Thông tin dự án", theo thứ tự của mẫu */
    public const COT_NOI_DUNG = ['du_kien_khoi_cong', 'du_kien_hoan_thanh', 'tinh_hinh', 'kho_khan', 'kien_nghi_xa', 'kien_nghi_so'];

    /** Ô "Thông tin dự án": tên / chủ đầu tư / công suất, mỗi thứ 1 dòng */
    public static function thongTin(array $m): string
    {
        return implode("\n", array_filter([$m['ten'], $m['chu_dau_tu'], $m['cong_suat']]));
    }

    /** Có cột "Chưa xác định xã" trong bảng thống kê không (chỉ khi có dự án chưa ghi xã) */
    public static function coCotChuaRo(array $bang): bool
    {
        return $bang['thong_ke']['tong']['chua_ro'] > 0;
    }

    public function tao(KyThang $ky, array $bang, ?string $donVi = null): TaoTepExcel
    {
        $x = new TaoTepExcel;
        $phu = '('.$ky->ten().($donVi ? ' — Đơn vị báo cáo: '.$donVi : '').')';

        // Sheet 1: bảng theo xã
        // Tỉ lệ độ rộng cột theo mẫu
        $rong = [6, 34, 18, 16, 40, 30, 26, 32];
        $n = count($rong);
        $s = $x->themSheet('THEO XÃ', $rong, 5);
        $x->dong($s, 1, [self::TIEU_DE], 'tieu_de', 18.75);
        $x->gop($s, 1, 1, 1, $n);
        $x->dong($s, 2, [$phu], 'nghieng');
        $x->gop($s, 2, 1, 2, $n);
        $x->dong($s, 4, ['TT', "Thông tin dự án\n(Tên, chủ đầu tư, công suất)", 'Dự kiến khởi công', 'Dự kiến hoàn thành',
            'Tình hình thực hiện', 'Khó khăn vướng mắc', 'Kiến nghị xử lý', null], 'dau_cot', 32);
        $x->dong($s, 5, [null, null, null, null, null, null, 'Xã/phường', 'Sở, ngành'], 'dau_cot', 18);
        for ($c = 1; $c <= 6; $c++) {
            $x->gop($s, 4, $c, 5, $c);
        }
        $x->gop($s, 4, 7, 4, 8);

        $r = 6;
        $rongNhom = array_sum($rong);
        foreach ($bang['xa'] as $xa) {
            $x->dong($s, $r, [['v' => $xa['ten'], 'kieu' => 'nhom']], 'nhom', max(18, TaoTepExcel::caoUocLuong($xa['ten'], $rongNhom)));
            $x->gop($s, $r, 1, $r++, $n);
            foreach ($xa['nhom'] as $nhom) {
                $x->dong($s, $r, [['v' => '* '.$nhom['ten'], 'kieu' => 'nhom']], 'nhom');
                $x->gop($s, $r, 1, $r++, $n);
                foreach ($nhom['muc'] as $m) {
                    $o = [['v' => (string) $m['tt'], 'kieu' => 'o_giua'], self::thongTin($m)];
                    foreach (self::COT_NOI_DUNG as $cot) {
                        $o[] = (string) ($m['noi_dung'][$cot] ?? '');
                    }
                    $x->dong($s, $r++, $o);
                }
            }
        }
        if (! $bang['xa']) {
            $x->dong($s, $r, ['Chưa có dự án nào.'], 'nghieng');
        }

        // Sheet 2: thống kê theo loại hình
        $chuaRo = self::coCotChuaRo($bang);
        $s = $x->themSheet('THỐNG KÊ LOẠI HÌNH', $chuaRo ? [7, 40, 16, 18, 18] : [7, 40, 16, 18]);
        $soCot = $chuaRo ? 5 : 4;
        $x->dong($s, 1, [self::TIEU_DE_THONG_KE], 'tieu_de', 18.75);
        $x->gop($s, 1, 1, 1, $soCot);
        $x->dong($s, 2, [$phu], 'nghieng');
        $x->gop($s, 2, 1, 2, $soCot);
        $x->dong($s, 4, array_merge(['STT', 'Loại hình', 'Trong 1 xã', 'Từ 2 xã trở lên'], $chuaRo ? ['Chưa xác định xã'] : []), 'dau_cot', 30);
        $r = 5;
        foreach ($bang['thong_ke']['hang'] as $i => $h) {
            $x->dong($s, $r++, array_merge([['v' => (string) ($i + 1), 'kieu' => 'o_giua'], $h['loai_hinh'], ['v' => $h['mot_xa'], 'kieu' => 'o_giua'],
                ['v' => $h['nhieu_xa'], 'kieu' => 'o_giua']], $chuaRo ? [['v' => $h['chua_ro'], 'kieu' => 'o_giua']] : []));
        }
        $tong = $bang['thong_ke']['tong'];
        $x->dong($s, $r, array_merge([null, 'Tổng cộng', ['v' => $tong['mot_xa'], 'kieu' => 'nhom_giua'], ['v' => $tong['nhieu_xa'], 'kieu' => 'nhom_giua']],
            $chuaRo ? [['v' => $tong['chua_ro'], 'kieu' => 'nhom_giua']] : []), 'nhom');

        // Sheet 3: dự án trên địa bàn từ 2 xã trở lên
        $s = $x->themSheet('DỰ ÁN NHIỀU XÃ', [7, 56, 60], 4);
        $x->dong($s, 1, ['DANH SÁCH DỰ ÁN TRÊN ĐỊA BÀN TỪ 2 XÃ TRỞ LÊN'], 'tieu_de', 18.75);
        $x->gop($s, 1, 1, 1, 3);
        $x->dong($s, 2, [$phu], 'nghieng');
        $x->gop($s, 2, 1, 2, 3);
        $x->dong($s, 4, ['STT', 'Tên dự án', 'Các xã, phường'], 'dau_cot', 24);
        $r = 5;
        foreach ($bang['nhieu_xa'] as $i => $nhom) {
            $x->dong($s, $r, [['v' => BaoCaoTuan::soLaMa($i + 1).'. '.$nhom['ten'], 'kieu' => 'nhom']], 'nhom');
            $x->gop($s, $r, 1, $r++, 3);
            foreach ($nhom['muc'] as $m) {
                $x->dong($s, $r++, [['v' => (string) $m['stt'], 'kieu' => 'o_giua'], $m['ten'], $m['xa']]);
            }
        }
        if (! $bang['nhieu_xa']) {
            $x->dong($s, $r, [null, 'Không có dự án nào trên địa bàn từ 2 xã trở lên.'], 'nghieng');
        }

        return $x;
    }
}
