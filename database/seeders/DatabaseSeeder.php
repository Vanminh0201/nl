<?php

namespace Database\Seeders;

use App\Models\DuAn;
use App\Models\LoaiHinhDuAn;
use App\Models\TrangThaiDuAn;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->danhMuc();

        User::updateOrCreate(
            ['email' => 'minhqld@gmail.com'],
            [
                'name' => 'Quản trị hệ thống',
                'password' => Hash::make('Diennang@123'),
                'vai_tro' => 'quan_tri',
                'don_vi' => 'Phòng Quản lý Công nghiệp',
            ]
        );

        User::updateOrCreate(
            ['email' => 'bunbap88@gmail.com'],
            [
                'name' => 'Chuyên viên tổng hợp',
                'password' => Hash::make('Diennang@123'),
                'vai_tro' => 'nhap_lieu',
                'don_vi' => 'Phòng Quản lý Công nghiệp',
            ]
        );

        // Dữ liệu thử để xem giao diện. Xoá trước khi nhập danh mục thật:
        // php artisan tinker --execute="App\Models\DuAn::where('ten','like','%dữ liệu thử%')->forceDelete();"
        if (! app()->environment('production') && DuAn::count() === 0) {
            $this->duLieuThu();
        }
    }

    private function danhMuc(): void
    {
        $loaiHinh = [
            ['ccn', 'Cụm công nghiệp', '#1f4b6e', 'ccn'],
            ['kcn', 'Khu công nghiệp', '#123f52', 'kcn'],
            ['che_bien', 'Chế biến nông lâm thuỷ sản', '#3f7a3a', 'che_bien'],
            ['vlxd', 'Sản xuất vật liệu xây dựng', '#8a6a2f', 'vlxd'],
            ['khoang_san', 'Khai thác, chế biến khoáng sản', '#6b4226', 'khoang_san'],
            ['thuy_dien', 'Thuỷ điện', '#1d6f86', 'thuy_dien'],
            ['dien_gio', 'Điện gió', '#5b7f9e', 'dien_gio'],
            ['dien_mat_troi', 'Điện mặt trời', '#b4531c', 'dien_mat_troi'],
            ['det_may', 'Dệt may, da giày', '#7a4569', 'det_may'],
            ['khac', 'Công nghiệp khác', '#5d6b61', 'khac'],
        ];
        foreach ($loaiHinh as $i => [$ma, $ten, $mau, $bieuTuong]) {
            LoaiHinhDuAn::updateOrCreate(['ma' => $ma], ['ten' => $ten, 'mau' => $mau, 'bieu_tuong' => $bieuTuong, 'thu_tu' => $i + 1]);
        }

        $trangThai = [
            ['Đang nghiên cứu, đề xuất', 'binh_thuong'],
            ['Đã chấp thuận chủ trương đầu tư', 'binh_thuong'],
            ['Đang giải phóng mặt bằng', 'binh_thuong'],
            ['Đang thi công', 'binh_thuong'],
            ['Đã đi vào hoạt động', 'hoan_thanh'],
            ['Chậm tiến độ', 'cham_tre'],
            ['Tạm dừng, thu hồi', 'cham_tre'],
        ];
        foreach ($trangThai as $i => [$ten, $nhom]) {
            TrangThaiDuAn::updateOrCreate(['ten' => $ten], ['nhom' => $nhom, 'thu_tu' => $i + 1]);
        }
    }

    private function duLieuThu(): void
    {
        $mau = [
            ['Cụm công nghiệp mẫu số 1 (dữ liệu thử)', 'ccn', 'phường Đông Hà', 'Đang thi công', 320, 45, null, 55, 16.8163, 107.1003],
            ['Nhà máy chế biến gỗ mẫu (dữ liệu thử)', 'che_bien', 'xã Cam Lộ', 'Đã đi vào hoạt động', 180, 12, '60.000 m³/năm', 100, 16.7869, 106.9862],
            ['Thuỷ điện mẫu (dữ liệu thử)', 'thuy_dien', 'xã Hướng Hoá', 'Đang giải phóng mặt bằng', 640, 28, '30 MW', 20, 16.6300, 106.6200],
            ['Điện gió mẫu (dữ liệu thử)', 'dien_gio', 'xã Hướng Linh', 'Đã đi vào hoạt động', 1800, 60, '48 MW', 100, 16.7500, 106.7300],
            ['Nhà máy xi măng mẫu (dữ liệu thử)', 'vlxd', 'xã Quảng Trạch', 'Đang thi công', 2400, 85, '1,2 triệu tấn/năm', 40, 17.7600, 106.4700],
            ['Khu công nghiệp mẫu (dữ liệu thử)', 'kcn', 'phường Ba Đồn', 'Đã chấp thuận chủ trương đầu tư', 1500, 210, null, 5, 17.7500, 106.4200],
        ];

        foreach ($mau as [$ten, $loai, $diaDiem, $trangThai, $von, $dt, $cs, $tienDo, $lat, $lng]) {
            DuAn::create([
                'ten' => $ten,
                'chu_dau_tu' => 'Đơn vị mẫu',
                'loai_hinh' => $loai,
                'dia_diem' => $diaDiem,
                'trang_thai' => $trangThai,
                'von_dang_ky' => $von,
                'dien_tich' => $dt,
                'cong_suat' => $cs,
                'tien_do' => $tienDo,
                'vi_do' => $lat,
                'kinh_do' => $lng,
                'pl_chu_truong' => true,
                'ghi_chu' => 'Bản ghi mẫu để xem giao diện, toạ độ là ước lượng.',
            ]);
        }
    }
}
