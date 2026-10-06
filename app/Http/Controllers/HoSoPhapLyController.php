<?php

namespace App\Http\Controllers;

use App\Models\DuAn;
use App\Models\DuAnHoSoTep;
use App\Models\DuAnVanBan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Văn bản của 8 hồ sơ pháp lý, tải lên ngay ở trang chi tiết dự án. Cùng bảng du_an_van_ban với mục
 * "Văn bản hồ sơ pháp lý" ở form sửa (cột ho_so = mã hồ sơ), nên hai nơi luôn khớp nhau. Tệp lưu ở ổ "local"
 * (storage/app/private), không đặt trong thư mục public, nên chỉ mở được qua route có kiểm tra quyền.
 */
class HoSoPhapLyController extends Controller
{
    public function store(Request $request, DuAn $duAn)
    {
        $data = $request->validate([
            'loai' => ['required', Rule::in(array_keys(DuAn::HO_SO_PHAP_LY))],
            'tep' => ['required', 'array', 'max:20'],
            'tep.*' => ['file', 'max:51200', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,zip,rar'],
            'so_van_ban' => ['nullable', 'string', 'max:100'],
            'ngay_van_ban' => ['nullable', 'date'],
        ], [
            'tep.required' => 'Chưa chọn tệp văn bản.',
            'tep.*.max' => 'Mỗi tệp tối đa 50 MB.',
            'tep.*.mimes' => 'Chỉ nhận tệp PDF, Word, Excel, ảnh (JPG, PNG) hoặc tệp nén (ZIP, RAR).',
        ], ['loai' => 'loại hồ sơ', 'so_van_ban' => 'số văn bản', 'ngay_van_ban' => 'ngày văn bản']);

        $thuTu = (int) $duAn->vanBanPhapLy()->max('thu_tu');
        $soTep = 0;
        foreach ($request->file('tep') as $tep) {
            $duAn->vanBanPhapLy()->create([
                'ho_so' => $data['loai'],
                'so_van_ban' => $data['so_van_ban'] ?? null,
                'ngay_van_ban' => $data['ngay_van_ban'] ?? null,
                'thu_tu' => ++$thuTu,
            ] + DuAnVanBan::luuTep($tep, $duAn->id));
            $soTep++;
        }

        // Đã có văn bản thì hồ sơ đó coi như "đã có"
        $duAn->dongBoTheoVanBan(['nguoi_cap_nhat_id' => $request->user()->id]);

        return redirect()->route('du-an.show', $duAn)
            ->with('thong_bao', "Đã thêm {$soTep} văn bản cho hồ sơ \"".DuAn::HO_SO_PHAP_LY[$data['loai']].'".');
    }

    public function xemVanBan(DuAnVanBan $vanBan)
    {
        // Người không xem được dự án (VD chủ đầu tư dự án khác — DuAn có global scope) thì không mở được tệp
        abort_unless(DuAn::query()->whereKey($vanBan->du_an_id)->exists(), 404);
        $duongDan = $vanBan->duongDanTep();
        abort_unless($duongDan !== null, 404);

        return response()->file($duongDan, ['Content-Disposition' => 'inline; filename="'.addcslashes($vanBan->tenTaiVe(), '"\\').'"'
            ."; filename*=UTF-8''".rawurlencode($vanBan->tenTaiVe())]);
    }

    public function xoaVanBan(DuAnVanBan $vanBan)
    {
        $duAn = DuAn::findOrFail($vanBan->du_an_id);
        $vanBan->xoaTep();
        $vanBan->delete();
        $duAn->dongBoTheoVanBan();

        return redirect()->route('du-an.show', $duAn)
            ->with('thong_bao', 'Đã xoá văn bản '.($vanBan->so_van_ban ?: $vanBan->ten_tep ?: '').' của hồ sơ "'.$vanBan->ten_ho_so.'".');
    }

    /** Tệp hồ sơ kiểu cũ (bảng du_an_ho_so_tep) — giữ đường mở cho liên kết cũ trong nhật ký */
    public function show(DuAnHoSoTep $tep)
    {
        abort_unless(DuAn::query()->whereKey($tep->du_an_id)->exists(), 404);
        abort_unless(Storage::disk('local')->exists($tep->duong_dan), 404);

        return Storage::disk('local')->response($tep->duong_dan, $tep->ten_tep);
    }

    public function destroy(DuAnHoSoTep $tep)
    {
        $duAnId = $tep->du_an_id;
        Storage::disk('local')->delete($tep->duong_dan);
        $tep->delete();

        return redirect()->route('du-an.show', $duAnId)->with('thong_bao', "Đã xoá tệp \"{$tep->ten_tep}\".");
    }
}
