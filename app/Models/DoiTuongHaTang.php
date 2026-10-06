<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoiTuongHaTang extends Model
{
    protected $table = 'doi_tuong_ha_tang';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'hinh_hoc' => 'array',
        'thuoc_tinh' => 'array',
        'tam_vi_do' => 'float',
        'tam_kinh_do' => 'float',
        'chieu_dai_m' => 'float',
        'hien_thi' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Sửa hình dạng tuyến (kéo đỉnh trên bản đồ) thì đo lại chiều dài
        static::saving(function (self $d) {
            if ($d->isDirty('hinh_hoc') || ($d->chieu_dai_m === null && array_key_exists('hinh_hoc', $d->getAttributes()))) {
                $d->chieu_dai_m = static::doChieuDaiMet($d->hinh_hoc);
            }
        });
    }

    /** Chiều dài (mét) của LineString/MultiLineString theo công thức haversine; điểm, vùng trả 0. */
    public static function doChieuDaiMet(?array $hinhHoc): float
    {
        $kieu = $hinhHoc['type'] ?? null;
        $cacDoan = match ($kieu) {
            'LineString' => [$hinhHoc['coordinates'] ?? []],
            'MultiLineString' => $hinhHoc['coordinates'] ?? [],
            default => [],
        };
        $tong = 0.0;
        foreach ($cacDoan as $doan) {
            for ($i = 1, $n = count($doan); $i < $n; $i++) {
                [$lng1, $lat1] = $doan[$i - 1];
                [$lng2, $lat2] = $doan[$i];
                $p1 = deg2rad((float) $lat1);
                $p2 = deg2rad((float) $lat2);
                $a = sin(($p2 - $p1) / 2) ** 2 + cos($p1) * cos($p2) * sin(deg2rad((float) $lng2 - (float) $lng1) / 2) ** 2;
                $tong += 2 * 6371008.8 * asin(min(1, sqrt($a)));
            }
        }

        return round($tong, 1);
    }

    /** Chiều dài (km) dùng để hiển thị: ưu tiên số ghi trong hồ sơ (thuộc tính chieu_dai_km), không có thì số đo trên bản đồ. */
    public static function chieuDaiKm(?array $thuocTinh, ?float $chieuDaiM): float
    {
        $ghi = (float) ($thuocTinh['chieu_dai_km'] ?? 0);

        return $ghi > 0 ? $ghi : (float) $chieuDaiM / 1000;
    }

    public const LOAI = [
        'tram' => 'Trạm biến áp',
        'duong_day' => 'Đường dây',
        'nha_may' => 'Nhà máy điện',
        'khac' => 'Đối tượng khác',
    ];

    public function lop(): BelongsTo
    {
        return $this->belongsTo(LopHaTang::class, 'lop_id');
    }

    public static function mauTheoDienAp(?int $kv): string
    {
        return match (true) {
            $kv === null => '#8a8f94',
            $kv >= 500 => '#c0392b',
            $kv >= 220 => '#d97b2a',
            $kv >= 110 => '#2f6fae',
            $kv >= 22 => '#6f8390',
            default => '#8a8f94',
        };
    }
}
