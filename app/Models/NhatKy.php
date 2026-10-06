<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NhatKy extends Model
{
    protected $table = 'nhat_ky';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'chi_tiet' => 'array',
        'created_at' => 'datetime',
    ];

    public const NHOM = [
        'thay_doi' => 'Thay đổi dữ liệu',
        'dang_nhap' => 'Đăng nhập',
        'tai_xuat' => 'Tải, xuất tệp',
        'xem' => 'Xem trang',
    ];

    public function nguoiDung()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
