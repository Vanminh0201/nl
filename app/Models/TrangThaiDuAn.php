<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class TrangThaiDuAn extends Model
{
    protected $table = 'trang_thai_du_an';

    protected $fillable = ['ten', 'nhom', 'thu_tu'];

    public const NHOM = [
        'binh_thuong' => 'Bình thường',
        'hoan_thanh' => 'Đã hoàn thành / đi vào hoạt động',
        'cham_tre' => 'Chậm tiến độ / tạm dừng',
    ];

    private static ?Collection $boNho = null;

    /** Toàn bộ trạng thái, xếp theo thứ tự hiển thị, khoá theo tên. Nhớ trong bộ nhớ cho hết vòng đời request. */
    public static function danhSach(): Collection
    {
        return static::$boNho ??= static::query()->orderBy('thu_tu')->orderBy('ten')->get()->keyBy('ten');
    }

    public static function lamMoiBoNho(): void
    {
        static::$boNho = null;
    }

    public static function tenTheoNhom(string $nhom): Collection
    {
        return static::danhSach()->filter(fn ($t) => $t->nhom === $nhom)->keys();
    }

    public function soDuAnDangDung(): int
    {
        return DuAn::withTrashed()->where('trang_thai', $this->ten)->count();
    }
}
