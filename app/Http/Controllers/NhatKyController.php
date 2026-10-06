<?php

namespace App\Http\Controllers;

use App\Models\NhatKy;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** Trang Nhật ký hoạt động — chỉ quản trị hệ thống */
class NhatKyController extends Controller
{
    /** Giờ hiển thị và lọc theo ngày (dữ liệu lưu theo UTC) */
    public const MUI_GIO = 'Asia/Ho_Chi_Minh';

    public function index(Request $request)
    {
        $loc = $request->validate([
            'tu_khoa' => ['nullable', 'string', 'max:200'],
            'nguoi_dung' => ['nullable', 'integer'],
            'nhom' => ['nullable', 'string', 'max:20'],
            'tu_ngay' => ['nullable', 'date_format:Y-m-d'],
            'den_ngay' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $nhom = $loc['nhom'] ?? 'chinh';

        $q = NhatKy::query();
        if ($nhom === 'chinh') {
            $q->where('nhom', '!=', 'xem');
        } elseif (isset(NhatKy::NHOM[$nhom])) {
            $q->where('nhom', $nhom);
        }
        if (! empty($loc['nguoi_dung'])) {
            $q->where('user_id', $loc['nguoi_dung']);
        }
        if ($tk = trim((string) ($loc['tu_khoa'] ?? ''))) {
            $q->where(fn ($w) => $w->where('hanh_dong', 'like', "%{$tk}%")->orWhere('doi_tuong', 'like', "%{$tk}%")
                ->orWhere('ten_nguoi_dung', 'like', "%{$tk}%")->orWhere('ip', 'like', "%{$tk}%")->orWhere('duong_dan', 'like', "%{$tk}%"));
        }
        if (! empty($loc['tu_ngay'])) {
            $q->where('created_at', '>=', Carbon::createFromFormat('Y-m-d', $loc['tu_ngay'], self::MUI_GIO)->startOfDay()->utc());
        }
        if (! empty($loc['den_ngay'])) {
            $q->where('created_at', '<=', Carbon::createFromFormat('Y-m-d', $loc['den_ngay'], self::MUI_GIO)->endOfDay()->utc());
        }

        $homNay = now(self::MUI_GIO)->startOfDay()->utc();

        return view('nhat-ky.index', [
            'dsNhatKy' => $q->orderByDesc('id')->paginate(50)->withQueryString(),
            'nhom' => $nhom,
            'dsNguoiDung' => User::orderBy('name')->get(['id', 'name', 'vai_tro']),
            'soLieu' => [
                'thay_doi' => NhatKy::where('nhom', 'thay_doi')->where('created_at', '>=', $homNay)->count(),
                'dang_nhap' => NhatKy::where('nhom', 'dang_nhap')->where('hanh_dong', 'like', 'Đăng nhập%')
                    ->where('hanh_dong', 'not like', '%không thành công%')->where('created_at', '>=', $homNay)->count(),
                'that_bai' => NhatKy::where('nhom', 'dang_nhap')->where('hanh_dong', 'like', '%không thành công%')
                    ->where('created_at', '>=', now()->subDays(7))->count(),
                'nguoi_dung' => NhatKy::where('created_at', '>=', $homNay)->whereNotNull('user_id')->distinct()->count('user_id'),
            ],
        ]);
    }
}
