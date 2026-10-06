<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/** Mỗi người dùng tự cập nhật thông tin tài khoản của mình; vai trò và quyền xem do quản trị cấp. */
class CaNhanController extends Controller
{
    private const THONG_BAO_LOI = [
        'required' => 'Chưa nhập :attribute.',
        'email' => ':Attribute không đúng định dạng.',
        'unique' => ':Attribute này đã được tài khoản khác sử dụng.',
        'max.string' => ':Attribute dài quá :max ký tự.',
        'min.string' => ':Attribute phải có ít nhất :min ký tự.',
        'confirmed' => 'Mật khẩu nhập lại không khớp.',
        'different' => 'Mật khẩu mới phải khác mật khẩu hiện tại.',
        'current_password' => 'Mật khẩu hiện tại không đúng.',
        'mat_khau_hien_tai.required' => 'Nhập mật khẩu hiện tại để xác nhận.',
    ];

    private const TEN_TRUONG = [
        'name' => 'tên',
        'email' => 'thư điện tử',
        'don_vi' => 'đơn vị',
        'mat_khau_hien_tai' => 'mật khẩu hiện tại',
        'mat_khau_moi' => 'mật khẩu mới',
    ];

    public function form(Request $request)
    {
        $nguoiDung = $request->user();

        return view('ca-nhan', [
            'nguoiDung' => $nguoiDung,
            'dsDuAn' => $nguoiDung->vai_tro === 'chu_dau_tu'
                ? $nguoiDung->duAn()->withoutGlobalScope('chu_dau_tu')->orderBy('ten')->get(['id', 'ten'])
                : collect(),
            'mucXem' => User::MUC_XEM,
        ]);
    }

    public function capNhat(Request $request)
    {
        $nguoiDung = $request->user();
        $doiEmail = trim((string) $request->input('email')) !== $nguoiDung->email;

        $data = $request->validateWithBag('thongTin', [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($nguoiDung->id)],
            'don_vi' => ['nullable', 'string', 'max:255'],
            // Đổi thư điện tử (tên đăng nhập) phải xác nhận lại bằng mật khẩu
            'mat_khau_hien_tai' => [Rule::requiredIf($doiEmail), 'nullable', 'current_password'],
        ], self::THONG_BAO_LOI, self::TEN_TRUONG);

        $nguoiDung->update(Arr::only($data, ['name', 'email', 'don_vi']));

        return back()->with('thong_bao', 'Đã cập nhật thông tin tài khoản.');
    }

    public function doiMatKhau(Request $request)
    {
        $data = $request->validateWithBag('matKhau', [
            'mat_khau_hien_tai' => ['required', 'current_password'],
            'mat_khau_moi' => ['required', 'string', 'min:6', 'confirmed', 'different:mat_khau_hien_tai'],
        ], self::THONG_BAO_LOI, self::TEN_TRUONG);

        $request->user()->update(['password' => $data['mat_khau_moi']]);
        $request->session()->regenerate();

        return back()->with('thong_bao', 'Đã đổi mật khẩu.');
    }
}
