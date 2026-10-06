<?php

namespace App\Http\Controllers;

use App\Models\BieuTuong;
use App\Models\DuAn;
use App\Models\LoaiHinhDuAn;
use App\Models\TrangThaiDuAn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DanhMucController extends Controller
{
    public function index()
    {
        return view('danh-muc.index', [
            'dsLoaiHinh' => LoaiHinhDuAn::query()->orderBy('thu_tu')->orderBy('ten')->get(),
            'dsTrangThai' => TrangThaiDuAn::query()->orderBy('thu_tu')->orderBy('ten')->get(),
            'dsBieuTuong' => BieuTuong::danhSach()->values(),
        ]);
    }

    public function themLoaiHinh(Request $request)
    {
        $data = $request->validate([
            'ma' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('loai_hinh_du_an', 'ma')],
            'ten' => ['required', 'string', 'max:150'],
            'mau' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'bieu_tuong' => ['required', Rule::exists('bieu_tuong', 'ma')],
            'thu_tu' => ['nullable', 'integer'],
        ], [], ['ma' => 'mã loại hình', 'ten' => 'tên loại hình', 'mau' => 'màu', 'bieu_tuong' => 'biểu tượng']);

        $data['thu_tu'] ??= (int) (LoaiHinhDuAn::max('thu_tu') ?? 0) + 1;
        LoaiHinhDuAn::create($data);
        LoaiHinhDuAn::lamMoiBoNho();

        return back()->with('thong_bao', "Đã thêm loại hình \"{$data['ten']}\".");
    }

    public function suaLoaiHinh(Request $request, LoaiHinhDuAn $loaiHinh)
    {
        $data = $request->validate([
            'ten' => ['required', 'string', 'max:150'],
            'mau' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'bieu_tuong' => ['required', Rule::exists('bieu_tuong', 'ma')],
            'thu_tu' => ['nullable', 'integer'],
        ], [], ['ten' => 'tên loại hình', 'mau' => 'màu', 'bieu_tuong' => 'biểu tượng']);

        $loaiHinh->update($data);
        LoaiHinhDuAn::lamMoiBoNho();

        return back()->with('thong_bao', "Đã cập nhật loại hình \"{$loaiHinh->ten}\".");
    }

    public function xoaLoaiHinh(LoaiHinhDuAn $loaiHinh)
    {
        $soDung = $loaiHinh->soDuAnDangDung();
        if ($soDung > 0) {
            return back()->withErrors([
                'loai_hinh' => "Không thể xoá \"{$loaiHinh->ten}\" vì đang có {$soDung} dự án dùng loại hình này.",
            ]);
        }

        $ten = $loaiHinh->ten;
        $loaiHinh->delete();
        LoaiHinhDuAn::lamMoiBoNho();

        return back()->with('thong_bao', "Đã xoá loại hình \"{$ten}\".");
    }

    public function themTrangThai(Request $request)
    {
        $data = $request->validate([
            'ten' => ['required', 'string', 'max:150', Rule::unique('trang_thai_du_an', 'ten')],
            'nhom' => ['required', Rule::in(array_keys(TrangThaiDuAn::NHOM))],
            'thu_tu' => ['nullable', 'integer'],
        ], [], ['ten' => 'tên trạng thái', 'nhom' => 'nhóm']);

        $data['thu_tu'] ??= (int) (TrangThaiDuAn::max('thu_tu') ?? 0) + 1;
        TrangThaiDuAn::create($data);
        TrangThaiDuAn::lamMoiBoNho();

        return back()->with('thong_bao', "Đã thêm trạng thái \"{$data['ten']}\".");
    }

    public function suaTrangThai(Request $request, TrangThaiDuAn $trangThai)
    {
        $data = $request->validate([
            'ten' => ['required', 'string', 'max:150', Rule::unique('trang_thai_du_an', 'ten')->ignore($trangThai->id)],
            'nhom' => ['required', Rule::in(array_keys(TrangThaiDuAn::NHOM))],
            'thu_tu' => ['nullable', 'integer'],
        ], [], ['ten' => 'tên trạng thái', 'nhom' => 'nhóm']);

        $tenCu = $trangThai->ten;

        DB::transaction(function () use ($trangThai, $data, $tenCu) {
            $trangThai->update($data);

            // trang_thai lưu thẳng tên, nên đổi tên phải kéo theo cập nhật các dự án đang dùng
            if ($tenCu !== $data['ten']) {
                DuAn::withTrashed()->where('trang_thai', $tenCu)->update(['trang_thai' => $data['ten']]);
            }
        });
        TrangThaiDuAn::lamMoiBoNho();

        return back()->with('thong_bao', "Đã cập nhật trạng thái \"{$data['ten']}\".");
    }

    public function xoaTrangThai(TrangThaiDuAn $trangThai)
    {
        $soDung = $trangThai->soDuAnDangDung();
        if ($soDung > 0) {
            return back()->withErrors([
                'trang_thai' => "Không thể xoá \"{$trangThai->ten}\" vì đang có {$soDung} dự án ở trạng thái này.",
            ]);
        }

        $ten = $trangThai->ten;
        $trangThai->delete();
        TrangThaiDuAn::lamMoiBoNho();

        return back()->with('thong_bao', "Đã xoá trạng thái \"{$ten}\".");
    }

    public function themBieuTuong(Request $request)
    {
        $data = $request->validate([
            'ma' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('bieu_tuong', 'ma')],
            'ten' => ['required', 'string', 'max:100'],
            'hinh' => ['required', Rule::in(BieuTuong::HINH_CO_SAN)],
            // SVG hiển thị qua CSS mask-image/thẻ <img>, trình duyệt không chạy script bên trong nên
            // an toàn dù không lọc nội dung file.
            'tep' => ['nullable', 'file', 'mimes:svg,png', 'max:300'],
            'thu_tu' => ['nullable', 'integer'],
        ], [], ['ma' => 'mã biểu tượng', 'ten' => 'tên biểu tượng', 'hinh' => 'hình', 'tep' => 'file icon']);

        if ($request->hasFile('tep')) {
            $data['tep'] = $this->luuTepBieuTuong($request->file('tep'));
        }

        $data['thu_tu'] ??= (int) (BieuTuong::max('thu_tu') ?? 0) + 1;
        BieuTuong::create($data);
        BieuTuong::lamMoiBoNho();

        return back()->with('thong_bao', "Đã thêm biểu tượng \"{$data['ten']}\".");
    }

    public function suaBieuTuong(Request $request, BieuTuong $bieuTuong)
    {
        $data = $request->validate([
            'ten' => ['required', 'string', 'max:100'],
            'hinh' => ['required', Rule::in(BieuTuong::HINH_CO_SAN)],
            'tep' => ['nullable', 'file', 'mimes:svg,png', 'max:300'],
            'xoa_tep' => ['nullable', 'boolean'],
            'thu_tu' => ['nullable', 'integer'],
        ], [], ['ten' => 'tên biểu tượng', 'hinh' => 'hình', 'tep' => 'file icon']);

        if ($request->hasFile('tep')) {
            $this->xoaTepBieuTuong($bieuTuong);
            $data['tep'] = $this->luuTepBieuTuong($request->file('tep'));
        } elseif ($request->boolean('xoa_tep')) {
            $this->xoaTepBieuTuong($bieuTuong);
            $data['tep'] = null;
        }
        unset($data['xoa_tep']);

        $bieuTuong->update($data);
        BieuTuong::lamMoiBoNho();

        return back()->with('thong_bao', "Đã cập nhật biểu tượng \"{$bieuTuong->ten}\".");
    }

    public function xoaBieuTuong(BieuTuong $bieuTuong)
    {
        $soDung = $bieuTuong->soDangDung();
        if ($soDung > 0) {
            return back()->withErrors([
                'bieu_tuong' => "Không thể xoá \"{$bieuTuong->ten}\" vì đang có {$soDung} nơi dùng biểu tượng này.",
            ]);
        }

        $this->xoaTepBieuTuong($bieuTuong);
        $ten = $bieuTuong->ten;
        $bieuTuong->delete();
        BieuTuong::lamMoiBoNho();

        return back()->with('thong_bao', "Đã xoá biểu tượng \"{$ten}\".");
    }

    private function luuTepBieuTuong(\Illuminate\Http\UploadedFile $tep): string
    {
        $thuMuc = 'bieu-tuong-tai-len';
        @mkdir(public_path($thuMuc), 0755, true);
        $tenTep = Str::random(24).'.'.$tep->getClientOriginalExtension();
        $tep->move(public_path($thuMuc), $tenTep);

        return $thuMuc.'/'.$tenTep;
    }

    private function xoaTepBieuTuong(BieuTuong $bieuTuong): void
    {
        if ($bieuTuong->tep && file_exists(public_path($bieuTuong->tep))) {
            @unlink(public_path($bieuTuong->tep));
        }
    }
}
