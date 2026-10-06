<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DangNhapController extends Controller
{
    public function form()
    {
        return view('dang-nhap');
    }

    public function dangNhap(Request $request)
    {
        $du_lieu = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [], ['email' => 'địa chỉ thư điện tử', 'password' => 'mật khẩu']);

        // Chống dò mật khẩu: sai 5 lần với cùng tài khoản (trong 5 phút) hoặc 20 lần từ cùng 1 địa chỉ IP
        // (trong 10 phút) thì tạm khoá đăng nhập đến hết khoảng thời gian đó
        $khoa = 'dang-nhap|'.Str::lower($du_lieu['email']).'|'.$request->ip();
        $khoaIp = 'dang-nhap-ip|'.$request->ip();
        $voiTaiKhoan = RateLimiter::tooManyAttempts($khoa, 5);
        $voiIp = RateLimiter::tooManyAttempts($khoaIp, 20);
        if ($voiTaiKhoan || $voiIp) {
            $giay = max($voiTaiKhoan ? RateLimiter::availableIn($khoa) : 0, $voiIp ? RateLimiter::availableIn($khoaIp) : 0);
            throw ValidationException::withMessages([
                'email' => 'Đăng nhập sai quá nhiều lần. Thử lại sau '.($giay > 90 ? ceil($giay / 60).' phút' : $giay.' giây').'.',
            ]);
        }

        if (! Auth::attempt($du_lieu, $request->boolean('ghi_nho'))) {
            RateLimiter::hit($khoa, 300);
            RateLimiter::hit($khoaIp, 600);
            throw ValidationException::withMessages([
                'email' => 'Thông tin đăng nhập không đúng.',
            ]);
        }
        RateLimiter::clear($khoa);

        if (! Auth::user()->da_duyet) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Tài khoản đang chờ quản trị viên duyệt, chưa thể đăng nhập.',
            ]);
        }

        $trangDau = Auth::user()->trangDau();
        if (! $trangDau) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Tài khoản chưa được cấp quyền xem mục nào — liên hệ quản trị hệ thống.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended($trangDau);
    }

    public function dangXuat(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('dang-nhap');
    }
}
