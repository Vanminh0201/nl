<?php

namespace App\Http\Controllers;

use App\Models\DuAn;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DangKyController extends Controller
{
    public function form()
    {
        return view('dang-ky', [
            // Dự án đã có tài khoản chủ đầu tư quản lý thì bỏ khỏi danh sách chọn — tránh
            // người đăng ký sau nhận nhầm dự án đã có người phụ trách.
            'dsDuAn' => DuAn::whereNull('chu_dau_tu_id')->orderBy('ten')->get(['id', 'ten', 'loai_hinh', 'dia_diem']),
        ]);
    }

    public function dangKy(Request $request)
    {
        // Chặn tạo tài khoản hàng loạt: tối đa 5 lần đăng ký mỗi giờ từ cùng 1 địa chỉ IP
        $khoa = 'dang-ky|'.$request->ip();
        if (RateLimiter::tooManyAttempts($khoa, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Đã đăng ký quá nhiều lần từ máy này. Thử lại sau '.ceil(RateLimiter::availableIn($khoa) / 60).' phút.',
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'don_vi' => ['nullable', 'string', 'max:255'],
            'du_an_ids' => ['nullable', 'array'],
            'du_an_ids.*' => [Rule::exists('du_an', 'id')->whereNull('chu_dau_tu_id')],
        ], [], [
            'name' => 'tên',
            'email' => 'thư điện tử',
            'password' => 'mật khẩu',
            'don_vi' => 'đơn vị',
        ]);

        RateLimiter::hit($khoa, 3600);
        $taiKhoan = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'don_vi' => $data['don_vi'] ?? null,
            'vai_tro' => 'chu_dau_tu',
            'da_duyet' => false,
        ]);

        // Gán ngay lúc đăng ký để quản trị viên thấy được nhà đầu tư nhận dự án nào khi
        // duyệt — tài khoản vẫn chưa đăng nhập được nên chưa có rủi ro gì trước khi duyệt.
        if (! empty($data['du_an_ids'])) {
            DuAn::whereIn('id', $data['du_an_ids'])->whereNull('chu_dau_tu_id')->update(['chu_dau_tu_id' => $taiKhoan->id]);
        }

        return redirect()->route('dang-nhap')->with('thong_bao',
            'Đăng ký thành công. Tài khoản đang chờ quản trị viên duyệt, vui lòng quay lại đăng nhập sau.');
    }
}
