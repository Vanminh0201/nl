<?php

namespace App\Http\Controllers;

use App\Models\CaiDat;
use App\Models\User;
use App\Support\BaoCaoSanLuong;
use App\Support\KyBaoCao;
use App\Support\KyThang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Quản trị chọn chế độ báo cáo (tuần, tháng, sản lượng điện) cho từng chủ đầu tư và hạn nộp báo cáo tháng, sản lượng. */
class CaiDatBaoCaoController extends Controller
{
    public function index()
    {
        return view('bao-cao.cai-dat', [
            'dsChuDauTu' => User::where('vai_tro', 'chu_dau_tu')->with(['duAn' => fn ($q) => $q->orderBy('ten')])
                ->get()->sortBy(fn (User $u) => [! $u->da_duyet, $u->don_vi ?: $u->name])->values(),
            'hanNop' => KyThang::ngayHan(),
            'tuanBatDau' => KyBaoCao::batDauApDung(),
            'thangBatDau' => KyThang::batDauApDung(),
            // Chọn được mốc áp dụng trong khoảng ~6 tháng trước đến vài kỳ tới
            'dsTuan' => KyBaoCao::hienTai()->sau()->sau()->sau()->sau()->ganDay(30),
            'dsThang' => KyThang::hienTai()->sau()->sau()->ganDay(9),
            'hanSanLuong' => BaoCaoSanLuong::ngayHan(),
            'sanLuongBatDau' => BaoCaoSanLuong::batDauApDung(),
            // Sản lượng có thể nhập bù các tháng trước nên cho chọn mốc xa hơn
            'dsThangSanLuong' => BaoCaoSanLuong::kyCanBao()->ganDay(25),
            'coVanHanh' => User::where('vai_tro', 'chu_dau_tu')->whereHas('duAn', fn ($q) => BaoCaoSanLuong::duAnDangVanHanh($q))->pluck('id')->flip(),
        ]);
    }

    public function luu(Request $request)
    {
        $data = $request->validate([
            'han_nop' => ['required', 'integer', 'between:1,28'],
            'tuan_bat_dau' => ['required', 'regex:/^\d{4}-W\d{2}$/'],
            'thang_bat_dau' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'ds' => ['nullable', 'array'],
            'ds.*' => ['integer'],
            'tuan' => ['nullable', 'array'],
            'thang' => ['nullable', 'array'],
            'san_luong' => ['nullable', 'array'],
            'han_san_luong' => ['required', 'integer', 'between:1,28'],
            'san_luong_bat_dau' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ], BaoCaoController::THONG_BAO_LOI + ['han_nop.between' => 'Hạn nộp phải là ngày từ 1 đến 28 (tháng nào cũng có).',
            'han_san_luong.between' => 'Hạn gửi sản lượng phải là ngày từ 1 đến 28.'],
            ['han_nop' => 'hạn nộp báo cáo tháng', 'tuan_bat_dau' => 'tuần bắt đầu áp dụng', 'thang_bat_dau' => 'tháng bắt đầu áp dụng',
                'han_san_luong' => 'hạn gửi báo cáo sản lượng', 'san_luong_bat_dau' => 'tháng bắt đầu báo cáo sản lượng']);

        $tuan = collect($data['tuan'] ?? [])->keys()->map(fn ($id) => (int) $id);
        $thang = collect($data['thang'] ?? [])->keys()->map(fn ($id) => (int) $id);
        $sanLuong = collect($data['san_luong'] ?? [])->keys()->map(fn ($id) => (int) $id);
        DB::transaction(function () use ($data, $tuan, $thang, $sanLuong) {
            CaiDat::dat('bao_cao_thang.han_nop', (string) $data['han_nop']);
            CaiDat::dat('bao_cao_san_luong.han_nop', (string) $data['han_san_luong']);
            if (KyThang::tuMa($data['san_luong_bat_dau'])) {
                CaiDat::dat('bao_cao_san_luong.thang_bat_dau', $data['san_luong_bat_dau']);
            }
            if (KyBaoCao::tuMa($data['tuan_bat_dau'])) {
                CaiDat::dat('bao_cao.tuan_bat_dau', $data['tuan_bat_dau']);
            }
            if (KyThang::tuMa($data['thang_bat_dau'])) {
                CaiDat::dat('bao_cao_thang.thang_bat_dau', $data['thang_bat_dau']);
            }
            // Chỉ cập nhật đúng các tài khoản có trên form (ô bỏ chọn không được gửi lên)
            User::where('vai_tro', 'chu_dau_tu')->whereIn('id', $data['ds'] ?? [])->get()
                ->each(fn (User $u) => $u->update([
                    'bc_tuan' => $tuan->contains($u->id),
                    'bc_thang' => $thang->contains($u->id),
                    'bc_san_luong' => $sanLuong->contains($u->id),
                ]));
        });

        return redirect()->route('bao-cao.cai-dat')->with('thong_bao', 'Đã lưu cài đặt báo cáo.');
    }
}
