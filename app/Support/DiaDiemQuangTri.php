<?php

namespace App\Support;

final class DiaDiemQuangTri
{
    public const DANH_SACH = [
        'Phường Đồng Thuận',
        'Phường Đồng Sơn',
        'Phường Đồng Hới',
        'Xã Minh Hóa',
        'Xã Dân Hóa',
        'Xã Tân Thành',
        'Xã Kim Điền',
        'Xã Kim Phú',
        'Xã Đồng Lê',
        'Xã Tuyên Sơn',
        'Xã Tuyên Lâm',
        'Xã Tuyên Phú',
        'Xã Tuyên Bình',
        'Xã Tuyên Hóa',
        'Phường Ba Đồn',
        'Xã Phú Trạch',
        'Xã Trung Thuần',
        'Xã Hòa Trạch',
        'Xã Tân Gianh',
        'Xã Quảng Trạch',
        'Phường Bắc Gianh',
        'Xã Nam Ba Đồn',
        'Xã Nam Gianh',
        'Xã Hoàn Lão',
        'Xã Bắc Trạch',
        'Xã Phong Nha',
        'Xã Bố Trạch',
        'Xã Thượng Trạch',
        'Xã Đông Trạch',
        'Xã Nam Trạch',
        'Xã Trường Sơn',
        'Xã Quảng Ninh',
        'Xã Ninh Châu',
        'Xã Trường Ninh',
        'Xã Lệ Ninh',
        'Xã Lệ Thủy',
        'Xã Cam Hồng',
        'Xã Sen Ngư',
        'Xã Tân Mỹ',
        'Xã Trường Phú',
        'Xã Kim Ngân',
        'Phường Đông Hà',
        'Phường Nam Đông Hà',
        'Phường Quảng Trị',
        'Xã Vĩnh Linh',
        'Xã Bến Quan',
        'Xã Vĩnh Hoàng',
        'Xã Vĩnh Thủy',
        'Xã Cửa Tùng',
        'Xã Khe Sanh',
        'Xã Lao Bảo',
        'Xã Hướng Lập',
        'Xã Hướng Phùng',
        'Xã Tân Lập',
        'Xã A Dơi',
        'Xã Lìa',
        'Xã Gio Linh',
        'Xã Cửa Việt',
        'Xã Bến Hải',
        'Xã Cồn Tiên',
        'Xã Hướng Hiệp',
        'Xã Đakrông',
        'Xã Ba Lòng',
        'Xã Tà Rụt',
        'Xã La Lay',
        'Xã Cam Lộ',
        'Xã Hiếu Giang',
        'Xã Triệu Phong',
        'Xã Nam Cửa Việt',
        'Xã Triệu Bình',
        'Xã Triệu Cơ',
        'Xã Ái Tử',
        'Xã Diên Sanh',
        'Xã Vĩnh Định',
        'Xã Hải Lăng',
        'Xã Nam Hải Lăng',
        'Xã Mỹ Thủy',
        'Đặc khu Cồn Cỏ',
    ];

    public static function chuanHoa(string $diaDiem): ?string
    {
        static $diaDiemTheoKhoa = null;
        if ($diaDiemTheoKhoa === null) {
            $diaDiemTheoKhoa = [];
            foreach (self::DANH_SACH as $diaDiemChuan) {
                $diaDiemTheoKhoa[mb_strtolower($diaDiemChuan)] = $diaDiemChuan;
            }
        }

        $diaDiemTim = mb_strtolower(preg_replace('/\s+/u', ' ', trim($diaDiem)));

        return $diaDiemTheoKhoa[$diaDiemTim] ?? null;
    }
}
