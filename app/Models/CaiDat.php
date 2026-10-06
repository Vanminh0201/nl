<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Cài đặt dạng khoá – giá trị, quản trị chỉnh trên giao diện (khác config/ chỉ sửa được bằng mã nguồn). */
class CaiDat extends Model
{
    protected $table = 'cai_dat';

    protected $primaryKey = 'khoa';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['khoa', 'gia_tri'];

    private static array $boNho = [];

    public static function lay(string $khoa, $macDinh = null)
    {
        if (! array_key_exists($khoa, self::$boNho)) {
            self::$boNho[$khoa] = self::query()->find($khoa)?->gia_tri;
        }

        return self::$boNho[$khoa] ?? $macDinh;
    }

    public static function dat(string $khoa, ?string $giaTri): void
    {
        self::query()->updateOrCreate(['khoa' => $khoa], ['gia_tri' => $giaTri]);
        self::$boNho[$khoa] = $giaTri;
    }

    public static function layJson(string $khoa): array
    {
        $gt = json_decode((string) self::lay($khoa), true);

        return is_array($gt) ? $gt : [];
    }

    public static function datJson(string $khoa, array $giaTri): void
    {
        self::dat($khoa, json_encode($giaTri, JSON_UNESCAPED_UNICODE));
    }
}
