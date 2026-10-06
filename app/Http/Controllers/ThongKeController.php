<?php

namespace App\Http\Controllers;

use App\Models\DuAn;
use App\Models\LoaiHinhDuAn;
use App\Models\TrangThaiDuAn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ThongKeController extends Controller
{
    public function index(Request $request)
    {
        $dk = $request->only(['tu_khoa', 'loai_hinh', 'trang_thai', 'dia_diem']);
        $co_ban = fn () => DuAn::query()->loc($dk);

        $soLuong = $co_ban()->selectRaw('
            COUNT(*) as so_du_an,
            COALESCE(SUM(von_dang_ky), 0) as tong_von,
            COALESCE(SUM(dien_tich), 0) as tong_dien_tich,
            COALESCE(SUM(cong_suat_mw), 0) as tong_cong_suat,
            SUM(CASE WHEN vi_do IS NULL THEN 1 ELSE 0 END) as thieu_toa_do
        ')->first();

        $tong = (object) [
            'so_du_an' => $soLuong->so_du_an,
            'tong_von' => $soLuong->tong_von,
            'tong_dien_tich' => $soLuong->tong_dien_tich,
            'tong_cong_suat' => $soLuong->tong_cong_suat,
            'thieu_toa_do' => $soLuong->thieu_toa_do,
            'dang_hoat_dong' => $co_ban()->whereIn('trang_thai', TrangThaiDuAn::tenTheoNhom('hoan_thanh'))->count(),
            'cham_tien_do' => $co_ban()->whereIn('trang_thai', TrangThaiDuAn::tenTheoNhom('cham_tre'))->count(),
        ];

        $theoLoaiHinh = $co_ban()
            ->select('loai_hinh', DB::raw('COUNT(*) as so_luong'), DB::raw('COALESCE(SUM(von_dang_ky), 0) as von'))
            ->groupBy('loai_hinh')
            ->orderByDesc('so_luong')
            ->get()
            ->map(fn ($r) => [
                'nhan' => LoaiHinhDuAn::danhSach()->get($r->loai_hinh)?->ten ?? $r->loai_hinh,
                'mau' => LoaiHinhDuAn::danhSach()->get($r->loai_hinh)?->mau ?? '#5d6b61',
                'so_luong' => (int) $r->so_luong,
                'von' => (float) $r->von,
            ]);

        $theoLoaiHinhCongSuat = $co_ban()
            ->select('loai_hinh', DB::raw('COALESCE(SUM(cong_suat_mw), 0) as cong_suat'))
            ->groupBy('loai_hinh')
            ->havingRaw('SUM(cong_suat_mw) > 0')
            ->orderByDesc('cong_suat')
            ->get()
            ->map(fn ($r) => [
                'nhan' => LoaiHinhDuAn::danhSach()->get($r->loai_hinh)?->ten ?? $r->loai_hinh,
                'mau' => LoaiHinhDuAn::danhSach()->get($r->loai_hinh)?->mau ?? '#5d6b61',
                'cong_suat' => (float) $r->cong_suat,
            ]);

        // Trạng thái chưa có màu riêng như loại hình — suy ra theo nhóm (nhom) để biểu đồ vẫn phân
        // biệt được màu có ý nghĩa (xanh lá = đã hoàn thành, đỏ = chậm/tạm dừng, xanh dương = bình thường)
        $mauTheoNhomTrangThai = ['hoan_thanh' => '#2f6b4f', 'cham_tre' => '#8f2c1e', 'binh_thuong' => '#1f4b6e'];
        $theoTrangThai = $co_ban()
            ->select('trang_thai', DB::raw('COUNT(*) as so_luong'))
            ->groupBy('trang_thai')
            ->orderByDesc('so_luong')
            ->get()
            ->map(fn ($r) => [
                'nhan' => $r->trang_thai,
                'so_luong' => (int) $r->so_luong,
                'mau' => $mauTheoNhomTrangThai[TrangThaiDuAn::danhSach()->get($r->trang_thai)?->nhom] ?? '#5d6b61',
            ]);

        $theoDiaBan = $co_ban()
            ->select('dia_diem', DB::raw('COUNT(*) as so_luong'), DB::raw('COALESCE(SUM(von_dang_ky), 0) as von'))
            ->groupBy('dia_diem')
            ->orderByDesc('so_luong')
            ->get()
            ->map(fn ($r) => [
                'nhan' => $r->dia_diem ?: 'Chưa xác định',
                'so_luong' => (int) $r->so_luong,
                'von' => (float) $r->von,
            ]);

        $hoSoThieu = collect(DuAn::HO_SO_PHAP_LY)->map(fn ($nhan, $cot) => [
            'nhan' => $nhan,
            'so_luong' => $co_ban()->where($cot, false)->count(),
        ])->sortByDesc('so_luong')->values();

        $dsLoaiHinh = LoaiHinhDuAn::danhSach();
        $dsTrangThai = TrangThaiDuAn::danhSach();

        return view('thong-ke.index', compact(
            'tong', 'theoLoaiHinh', 'theoLoaiHinhCongSuat', 'theoTrangThai', 'theoDiaBan', 'hoSoThieu', 'dk',
            'dsLoaiHinh', 'dsTrangThai'
        ));
    }
}
