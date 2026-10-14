<?php

namespace Tests\Unit\Support;

use App\Support\SanLuongDienUocTinh;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SanLuongDienUocTinhTest extends TestCase
{
    public function test_distributes_remaining_cumulative_generation_evenly_across_missing_months(): void
    {
        $duLieu = SanLuongDienUocTinh::choDuAn(
            'Nhà máy Nhiệt điện Quảng Trạch I',
            '2026-04',
            2026,
            [9 => 213_206_405.0],
        );

        $this->assertSame('12/04/2026', $duLieu['ngay_hoa_dong_bo']);
        $this->assertSame(550_676_655.0, $duLieu['luy_ke']);
        $this->assertSame(213_206_405.0, $duLieu['san_luong_thang_gan_nhat']);
        $this->assertSame(false, $duLieu['thang_gan_nhat_theo_tai_lieu']);
        $this->assertSame([
            4 => 67_494_050.0,
            5 => 67_494_050.0,
            6 => 67_494_050.0,
            7 => 67_494_050.0,
            8 => 67_494_050.0,
        ], $duLieu['thang']);
        $this->assertSame(550_676_655.0, array_sum($duLieu['thang']) + $duLieu['san_luong_thang_gan_nhat']);
    }

    public function test_preserves_reported_months_and_estimates_only_missing_months(): void
    {
        $duLieu = SanLuongDienUocTinh::choDuAn(
            'NMNĐ Quảng Trạch I',
            '2026-04',
            2026,
            [4 => 100_000_000.0, 9 => 213_206_405.0],
        );

        $this->assertSame([
            5 => 59_367_562.5,
            6 => 59_367_562.5,
            7 => 59_367_562.5,
            8 => 59_367_562.5,
        ], $duLieu['thang']);
    }

    public function test_uses_the_latest_supplied_report_value_when_no_official_month_is_recorded(): void
    {
        $duLieu = SanLuongDienUocTinh::choDuAn(
            'NMNĐ Quảng Trạch I',
            '2026-04',
            2026,
            [],
        );

        $this->assertSame(true, $duLieu['thang_gan_nhat_theo_tai_lieu']);
        $this->assertSame(213_206_405.0, $duLieu['san_luong_thang_gan_nhat']);
        $this->assertSame(550_676_655.0, array_sum($duLieu['thang']) + $duLieu['san_luong_thang_gan_nhat']);
    }

    public function test_does_not_create_estimates_when_recorded_months_exceed_the_reported_cumulative_total(): void
    {
        $duLieu = SanLuongDienUocTinh::choDuAn(
            'NMNĐ Quảng Trạch I',
            '2026-04',
            2026,
            [4 => 400_000_000.0, 9 => 213_206_405.0],
        );

        $this->assertSame([], $duLieu['thang']);
        $this->assertSame(
            'Không thể phân bổ ước tính vì số liệu tháng đã ghi nhận vượt lũy kế theo tài liệu.',
            $duLieu['canh_bao'],
        );
    }

    #[DataProvider('khongApDungChoDuLieuKhac')]
    public function test_does_not_add_estimates_to_other_projects_or_years(string $tenDuAn, string $phatDienTu, int $nam): void
    {
        $duLieu = SanLuongDienUocTinh::choDuAn($tenDuAn, $phatDienTu, $nam, []);

        $this->assertSame(null, $duLieu);
    }

    public static function khongApDungChoDuLieuKhac(): array
    {
        return [
            'project two' => ['NMNĐ Quảng Trạch II', '2026-04', 2026],
            'other project' => ['Nhà máy điện khác', '2026-04', 2026],
            'other start month' => ['NMNĐ Quảng Trạch I', '2026-05', 2026],
            'other year' => ['NMNĐ Quảng Trạch I', '2026-04', 2025],
        ];
    }
}
