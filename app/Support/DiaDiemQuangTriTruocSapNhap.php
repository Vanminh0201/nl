<?php

namespace App\Support;

final class DiaDiemQuangTriTruocSapNhap
{
    /** Nguồn: bộ DVHC của TCTK qua dvhcvn, dữ liệu tại 28/02/2025, trước sáp nhập tỉnh. */
    private const THEO_HUYEN = [
        'Quảng Bình' => [
            'Thành Phố Đồng Hới' => [
                'Phường Hải Thành', 'Phường Đồng Phú', 'Phường Bắc Lý', 'Phường Nam Lý', 'Phường Đồng Hải',
                'Phường Đồng Sơn', 'Phường Phú Hải', 'Phường Bắc Nghĩa', 'Phường Đức Ninh Đông', 'Xã Quang Phú',
                'Xã Lộc Ninh', 'Xã Bảo Ninh', 'Xã Nghĩa Ninh', 'Xã Thuận Đức', 'Xã Đức Ninh',
            ],
            'Huyện Minh Hóa' => [
                'Thị trấn Quy Đạt', 'Xã Dân Hóa', 'Xã Trọng Hóa', 'Xã Hồng Hóa', 'Xã Tân Thành', 'Xã Hóa Hợp',
                'Xã Xuân Hóa', 'Xã Yên Hóa', 'Xã Minh Hóa', 'Xã Tân Hóa', 'Xã Hóa Sơn', 'Xã Trung Hóa',
                'Xã Thượng Hóa',
            ],
            'Huyện Tuyên Hóa' => [
                'Thị trấn Đồng Lê', 'Xã Hương Hóa', 'Xã Kim Hóa', 'Xã Thanh Hóa', 'Xã Thanh Thạch',
                'Xã Thuận Hóa', 'Xã Lâm Hóa', 'Xã Lê Hóa', 'Xã Sơn Hóa', 'Xã Đồng Hóa', 'Xã Ngư Hóa',
                'Xã Thạch Hóa', 'Xã Đức Hóa', 'Xã Phong Hóa', 'Xã Mai Hóa', 'Xã Tiến Hóa', 'Xã Châu Hóa',
                'Xã Cao Quảng', 'Xã Văn Hóa',
            ],
            'Huyện Quảng Trạch' => [
                'Xã Quảng Hợp', 'Xã Quảng Kim', 'Xã Quảng Đông', 'Xã Quảng Phú', 'Xã Quảng Châu',
                'Xã Quảng Thạch', 'Xã Quảng Lưu', 'Xã Quảng Tùng', 'Xã Cảnh Dương', 'Xã Quảng Tiến',
                'Xã Quảng Hưng', 'Xã Quảng Xuân', 'Xã Liên Trường', 'Xã Quảng Phương', 'Xã Phù Cảnh',
                'Xã Quảng Thanh',
            ],
            'Huyện Bố Trạch' => [
                'Thị trấn Hoàn Lão', 'Thị trấn NT Việt Trung', 'Xã Xuân Trạch', 'Xã Hạ Mỹ', 'Xã Bắc Trạch',
                'Xã Lâm Trạch', 'Xã Thanh Trạch', 'Xã Liên Trạch', 'Xã Phúc Trạch', 'Xã Cự Nẫm',
                'Xã Hải Phú', 'Xã Thượng Trạch', 'Xã Sơn Lộc', 'Xã Hưng Trạch', 'Xã Đồng Trạch',
                'Xã Đức Trạch', 'Thị trấn Phong Nha', 'Xã Vạn Trạch', 'Xã Phú Định', 'Xã Trung Trạch',
                'Xã Tây Trạch', 'Xã Hòa Trạch', 'Xã Đại Trạch', 'Xã Nhân Trạch', 'Xã Tân Trạch', 'Xã Lý Nam',
            ],
            'Huyện Quảng Ninh' => [
                'Xã Trường Sơn', 'Thị trấn Quán Hàu', 'Xã Vĩnh Ninh', 'Xã Võ Ninh', 'Xã Hải Ninh',
                'Xã Hàm Ninh', 'Xã Duy Ninh', 'Xã Gia Ninh', 'Xã Trường Xuân', 'Xã Hiền Ninh', 'Xã Tân Ninh',
                'Xã Xuân Ninh', 'Xã An Ninh', 'Xã Vạn Ninh',
            ],
            'Huyện Lệ Thủy' => [
                'Thị trấn NT Lệ Ninh', 'Thị trấn Kiến Giang', 'Xã Hồng Thủy', 'Xã Ngư Thủy Bắc',
                'Xã Hoa Thủy', 'Xã Thanh Thủy', 'Xã An Thủy', 'Xã Phong Thủy', 'Xã Cam Thủy', 'Xã Ngân Thủy',
                'Xã Sơn Thủy', 'Xã Lộc Thủy', 'Xã Liên Thủy', 'Xã Hưng Thủy', 'Xã Dương Thủy', 'Xã Tân Thủy',
                'Xã Phú Thủy', 'Xã Xuân Thủy', 'Xã Mỹ Thủy', 'Xã Ngư Thủy', 'Xã Mai Thủy', 'Xã Sen Thủy',
                'Xã Thái Thủy', 'Xã Kim Thủy', 'Xã Trường Thủy', 'Xã Lâm Thủy',
            ],
            'Thị xã Ba Đồn' => [
                'Phường Ba Đồn', 'Phường Quảng Long', 'Phường Quảng Thọ', 'Xã Quảng Tiên', 'Xã Quảng Trung',
                'Phường Quảng Phong', 'Phường Quảng Thuận', 'Xã Quảng Tân', 'Xã Quảng Hải', 'Xã Quảng Sơn',
                'Xã Quảng Lộc', 'Xã Quảng Thủy', 'Xã Quảng Văn', 'Phường Quảng Phúc', 'Xã Quảng Hòa',
                'Xã Quảng Minh',
            ],
        ],
        'Quảng Trị' => [
            'Thành phố Đông Hà' => [
                'Phường Đông Giang', 'Phường 1', 'Phường Đông Lễ', 'Phường Đông Thanh', 'Phường 2',
                'Phường 4', 'Phường 5', 'Phường Đông Lương', 'Phường 3',
            ],
            'Thị xã Quảng Trị' => [
                'Phường 1', 'Phường An Đôn', 'Phường 2', 'Phường 3', 'Xã Hải Lệ',
            ],
            'Huyện Vĩnh Linh' => [
                'Thị trấn Hồ Xá', 'Thị trấn Bến Quan', 'Xã Vĩnh Thái', 'Xã Vĩnh Tú', 'Xã Vĩnh Chấp',
                'Xã Trung Nam', 'Xã Kim Thạch', 'Xã Vĩnh Long', 'Xã Vĩnh Khê', 'Xã Vĩnh Hòa',
                'Xã Vĩnh Thủy', 'Xã Vĩnh Lâm', 'Xã Hiền Thành', 'Thị trấn Cửa Tùng', 'Xã Vĩnh Hà',
                'Xã Vĩnh Sơn', 'Xã Vĩnh Giang', 'Xã Vĩnh Ô',
            ],
            'Huyện Hướng Hóa' => [
                'Thị trấn Khe Sanh', 'Thị trấn Lao Bảo', 'Xã Hướng Lập', 'Xã Hướng Việt', 'Xã Hướng Phùng',
                'Xã Hướng Sơn', 'Xã Hướng Linh', 'Xã Tân Hợp', 'Xã Hướng Tân', 'Xã Tân Thành', 'Xã Tân Long',
                'Xã Tân Lập', 'Xã Tân Liên', 'Xã Húc', 'Xã Thuận', 'Xã Hướng Lộc', 'Xã Ba Tầng', 'Xã Thanh',
                'Xã A Dơi', 'Xã Lìa', 'Xã Xy',
            ],
            'Huyện Gio Linh' => [
                'Thị trấn Gio Linh', 'Thị trấn Cửa Việt', 'Xã Trung Giang', 'Xã Trung Hải', 'Xã Trung Sơn',
                'Xã Phong Bình', 'Xã Gio Mỹ', 'Xã Gio Hải', 'Xã Gio An', 'Xã Linh Trường', 'Xã Gio Sơn',
                'Xã Gio Mai', 'Xã Hải Thái', 'Xã Gio Quang',
            ],
            'Huyện Đa Krông' => [
                'Thị trấn Krông Klang', 'Xã Mò Ó', 'Xã Hướng Hiệp', 'Xã Đa Krông', 'Xã Triệu Nguyên',
                'Xã Ba Lòng', 'Xã Ba Nang', 'Xã Tà Long', 'Xã Húc Nghì', 'Xã A Vao', 'Xã Tà Rụt',
                'Xã A Bung', 'Xã A Ngo',
            ],
            'Huyện Cam Lộ' => [
                'Thị trấn Cam Lộ', 'Xã Cam Tuyền', 'Xã Thanh An', 'Xã Cam Thủy', 'Xã Cam Thành',
                'Xã Cam Hiếu', 'Xã Cam Chính', 'Xã Cam Nghĩa',
            ],
            'Huyện Triệu Phong' => [
                'Thị Trấn Ái Tử', 'Xã Triệu Tân', 'Xã Triệu Phước', 'Xã Triệu Độ', 'Xã Triệu Trạch',
                'Xã Triệu Thuận', 'Xã Triệu Đại', 'Xã Triệu Hòa', 'Xã Triệu Cơ', 'Xã Triệu Long',
                'Xã Triệu Tài', 'Xã Triệu Trung', 'Xã Triệu Ái', 'Xã Triệu Thượng', 'Xã Triệu Giang',
                'Xã Triệu Thành',
            ],
            'Huyện Hải Lăng' => [
                'Thị trấn Diên Sanh', 'Xã Hải An', 'Xã Hải Bình', 'Xã Hải Quy', 'Xã Hải Hưng', 'Xã Hải Phú',
                'Xã Hải Thượng', 'Xã Hải Dương', 'Xã Hải Định', 'Xã Hải Lâm', 'Xã Hải Phong', 'Xã Hải Trường',
                'Xã Hải Sơn', 'Xã Hải Chánh', 'Xã Hải Khê',
            ],
        ],
    ];

    public static function nhomDanhSach(): array
    {
        $danhSach = [];

        foreach (self::THEO_HUYEN as $tinh => $dsHuyen) {
            foreach ($dsHuyen as $huyen => $dsXa) {
                $danhSach[$tinh][$huyen] = array_map(
                    fn (string $xa): string => self::giaTri($xa, $huyen, $tinh),
                    $dsXa
                );
            }
        }

        return $danhSach;
    }

    public static function danhSach(): array
    {
        $danhSach = [];

        foreach (self::nhomDanhSach() as $dsHuyen) {
            foreach ($dsHuyen as $dsXa) {
                array_push($danhSach, ...$dsXa);
            }
        }

        return $danhSach;
    }

    public static function chuanHoa(string $diaDiem): ?string
    {
        static $diaDiemTheoKhoa = null;
        static $diaDiemTheoTen = null;

        if ($diaDiemTheoKhoa === null) {
            $diaDiemTheoKhoa = [];
            $diaDiemTheoTen = [];

            foreach (self::nhomDanhSach() as $dsHuyen) {
                foreach ($dsHuyen as $dsXa) {
                    foreach ($dsXa as $diaDiemChuan) {
                        $diaDiemTheoKhoa[self::khoa($diaDiemChuan)] = $diaDiemChuan;
                        $tenXa = explode(' — ', $diaDiemChuan, 2)[0];
                        $diaDiemTheoTen[self::khoa($tenXa)][] = $diaDiemChuan;
                    }
                }
            }
        }

        $diaDiemTim = self::khoa($diaDiem);
        if (isset($diaDiemTheoKhoa[$diaDiemTim])) {
            return $diaDiemTheoKhoa[$diaDiemTim];
        }

        $khopTenXa = $diaDiemTheoTen[$diaDiemTim] ?? [];

        return count($khopTenXa) === 1 ? $khopTenXa[0] : null;
    }

    private static function giaTri(string $xa, string $huyen, string $tinh): string
    {
        $xa = preg_replace('/\s+/u', ' ', trim($xa));

        return "{$xa} — {$huyen}, tỉnh {$tinh}";
    }

    private static function khoa(string $diaDiem): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($diaDiem)));
    }
}
