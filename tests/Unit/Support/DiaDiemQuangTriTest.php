<?php

namespace Tests\Unit\Support;

use App\Support\DiaDiemQuangTri;
use App\Support\DiaDiemQuangTriTruocSapNhap;
use PHPUnit\Framework\TestCase;

class DiaDiemQuangTriTest extends TestCase
{
    public function test_quang_tri_list_contains_all_78_new_commune_level_units_without_duplicates(): void
    {
        $this->assertCount(78, DiaDiemQuangTri::DANH_SACH);
        $this->assertCount(78, array_unique(DiaDiemQuangTri::DANH_SACH));
        $this->assertContains('Phường Đông Hà', DiaDiemQuangTri::DANH_SACH);
        $this->assertContains('Xã Hướng Hiệp', DiaDiemQuangTri::DANH_SACH);
        $this->assertContains('Đặc khu Cồn Cỏ', DiaDiemQuangTri::DANH_SACH);
    }

    public function test_previous_quang_binh_and_quang_tri_lists_include_264_units_and_qualify_ambiguous_names(): void
    {
        $danhSach = DiaDiemQuangTriTruocSapNhap::danhSach();

        $this->assertCount(264, $danhSach);
        $this->assertCount(264, array_unique($danhSach));
        $this->assertSame(
            'Xã Triệu Nguyên — Huyện Đa Krông, tỉnh Quảng Trị',
            DiaDiemQuangTriTruocSapNhap::chuanHoa('Xã Triệu Nguyên')
        );
        $this->assertSame(
            'Phường 1 — Thành phố Đông Hà, tỉnh Quảng Trị',
            DiaDiemQuangTriTruocSapNhap::chuanHoa('Phường 1 — Thành phố Đông Hà, tỉnh Quảng Trị')
        );
        $this->assertNull(DiaDiemQuangTriTruocSapNhap::chuanHoa('Phường 1'));
    }
}
