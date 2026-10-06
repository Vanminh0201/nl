<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class LoaiHinhDuAn extends Model
{
    protected $table = 'loai_hinh_du_an';

    protected $fillable = ['ma', 'ten', 'mau', 'bieu_tuong', 'thu_tu'];

    private static ?Collection $boNho = null;

    /** Toàn bộ loại hình, xếp theo thứ tự hiển thị, khoá theo mã. Nhớ trong bộ nhớ cho hết vòng đời request. */
    public static function danhSach(): Collection
    {
        return static::$boNho ??= static::query()->orderBy('thu_tu')->orderBy('ten')->get()->keyBy('ma');
    }

    public static function lamMoiBoNho(): void
    {
        static::$boNho = null;
    }

    public function soDuAnDangDung(): int
    {
        return DuAn::withTrashed()->where('loai_hinh', $this->ma)->count();
    }
}
