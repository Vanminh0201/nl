<?php

namespace App\Observers;

use App\Support\NhatKyHoatDong;
use Illuminate\Database\Eloquent\Model;

/** Gom các thay đổi (thêm/sửa/xoá) của model để ghi vào nhật ký hoạt động */
class GhiThayDoiModel
{
    public function created(Model $m): void
    {
        app(NhatKyHoatDong::class)->ghiNhanModel($m, 'tao');
    }

    public function updated(Model $m): void
    {
        app(NhatKyHoatDong::class)->ghiNhanModel($m, 'sua');
    }

    public function deleted(Model $m): void
    {
        app(NhatKyHoatDong::class)->ghiNhanModel($m, 'xoa');
    }
}
