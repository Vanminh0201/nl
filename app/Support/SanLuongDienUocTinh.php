<?php

namespace App\Support;

use Illuminate\Support\Str;

final class SanLuongDienUocTinh
{
    public const NAM = 2026;

    private const THANG_BAT_DAU = 4;

    private const THANG_GAN_NHAT = 9;

    /** Số liệu từ tài liệu người dùng cung cấp; chỉ hiển thị riêng, không ghi vào bảng sản lượng chính thức. */
    private const NGAY_HOA_DONG_BO = '12/04/2026';

    private const LUY_KE_KWH = 550_676_655.0;

    private const SAN_LUONG_THANG_GAN_NHAT_KWH = 213_206_405.0;

    /**
     * @param  array<int, float|null>  $sanLuongThucTe  Số liệu chính thức, khóa theo tháng
     * @return array{
     *     ngay_hoa_dong_bo: string,
     *     luy_ke: float,
     *     thang_gan_nhat: int,
     *     san_luong_thang_gan_nhat: float,
     *     thang_gan_nhat_theo_tai_lieu: bool,
     *     thang: array<int, float>,
     *     canh_bao: ?string
     * }|null
     */
    public static function choDuAn(string $tenDuAn, ?string $phatDienTu, int $nam, array $sanLuongThucTe): ?array
    {
        if (
            $nam !== self::NAM
            || $phatDienTu !== '2026-04'
            || preg_match('/\bquang\s+trach\s+i\b/', Str::lower(Str::ascii($tenDuAn))) !== 1
        ) {
            return null;
        }

        $thangGanNhatTheoTaiLieu = ! isset($sanLuongThucTe[self::THANG_GAN_NHAT]);
        $sanLuongThangGanNhat = $thangGanNhatTheoTaiLieu
            ? self::SAN_LUONG_THANG_GAN_NHAT_KWH
            : (float) $sanLuongThucTe[self::THANG_GAN_NHAT];
        $cacThangDaCo = [];
        $thangCanUocTinh = [];

        for ($thang = self::THANG_BAT_DAU; $thang < self::THANG_GAN_NHAT; $thang++) {
            if (isset($sanLuongThucTe[$thang])) {
                $cacThangDaCo[$thang] = (float) $sanLuongThucTe[$thang];
            } else {
                $thangCanUocTinh[] = $thang;
            }
        }

        $luongConLai = self::LUY_KE_KWH - array_sum($cacThangDaCo) - $sanLuongThangGanNhat;
        $cacThangUocTinh = [];
        $canhBao = null;
        if ($luongConLai < 0) {
            $canhBao = 'Không thể phân bổ ước tính vì số liệu tháng đã ghi nhận vượt lũy kế theo tài liệu.';
        } elseif ($thangCanUocTinh !== []) {
            $mucTrungBinh = floor(($luongConLai / count($thangCanUocTinh)) * 10) / 10;
            $phanDu = round($luongConLai - $mucTrungBinh * count($thangCanUocTinh), 1);

            foreach ($thangCanUocTinh as $i => $thang) {
                $cacThangUocTinh[$thang] = $mucTrungBinh + ($i === array_key_last($thangCanUocTinh) ? $phanDu : 0);
            }
        }

        ksort($cacThangUocTinh);

        return [
            'ngay_hoa_dong_bo' => self::NGAY_HOA_DONG_BO,
            'luy_ke' => self::LUY_KE_KWH,
            'thang_gan_nhat' => self::THANG_GAN_NHAT,
            'san_luong_thang_gan_nhat' => $sanLuongThangGanNhat,
            'thang_gan_nhat_theo_tai_lieu' => $thangGanNhatTheoTaiLieu,
            'thang' => $canhBao === null ? $cacThangUocTinh : [],
            'canh_bao' => $canhBao,
        ];
    }
}
