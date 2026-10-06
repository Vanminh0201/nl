<?php

namespace App\Support;

/**
 * Rút gọn hình học đường/vùng theo mức phóng của bản đồ (Douglas–Peucker) để trang Bản đồ tải nhẹ: xem toàn tỉnh
 * 1 điểm ảnh ≈ vài trăm mét nên không cần hàng nghìn đỉnh mỗi tuyến; phóng gần thì giữ gần như nguyên vẹn.
 * Chỉ dùng cho hiển thị — dữ liệu gốc trong CSDL không đổi.
 */
class DonGianHinhHoc
{
    /** Ngưỡng rút gọn (độ) ≈ 0,6 điểm ảnh ở mức phóng $zoom; từ mức 16 trở lên không rút gọn */
    public static function nguong(int $zoom): float
    {
        return $zoom >= 16 ? 0.0 : 0.6 * 360 / (256 * 2 ** $zoom);
    }

    /** Số chữ số thập phân đủ cho mức phóng (làm tròn toạ độ cho gói dữ liệu nhỏ hơn) */
    public static function soLe(int $zoom): int
    {
        return $zoom <= 9 ? 4 : ($zoom <= 12 ? 5 : 6);
    }

    public static function rutGon(?array $hinhHoc, int $zoom): ?array
    {
        $kieu = $hinhHoc['type'] ?? null;
        $tol = static::nguong($zoom);
        $le = static::soLe($zoom);
        $duong = fn (array $ds) => static::lamTron($tol > 0 ? static::dp($ds, $tol) : $ds, $le);
        $vong = function (array $ds) use ($tol, $le) {
            $r = $tol > 0 ? static::dp($ds, $tol) : $ds;
            return count($r) >= 4 ? static::lamTron($r, $le) : static::lamTron($ds, $le); // vòng quá nhỏ thì giữ nguyên
        };

        return match ($kieu) {
            'LineString' => ['type' => $kieu, 'coordinates' => $duong($hinhHoc['coordinates'])],
            'MultiLineString' => ['type' => $kieu, 'coordinates' => array_map($duong, $hinhHoc['coordinates'])],
            'Polygon' => ['type' => $kieu, 'coordinates' => array_map($vong, $hinhHoc['coordinates'])],
            'MultiPolygon' => ['type' => $kieu, 'coordinates' => array_map(fn ($p) => array_map($vong, $p), $hinhHoc['coordinates'])],
            default => $hinhHoc,
        };
    }

    /** Douglas–Peucker không đệ quy; giữ đầu, cuối và mọi đỉnh lệch quá $tol khỏi đoạn nối */
    private static function dp(array $ds, float $tol): array
    {
        $n = count($ds);
        if ($n <= 2) {
            return $ds;
        }
        $giu = array_fill(0, $n, false);
        $giu[0] = $giu[$n - 1] = true;
        $ngan = [[0, $n - 1]];
        while ($ngan) {
            [$a, $b] = array_pop($ngan);
            [$x1, $y1] = $ds[$a];
            [$x2, $y2] = $ds[$b];
            $dx = $x2 - $x1;
            $dy = $y2 - $y1;
            $l2 = $dx * $dx + $dy * $dy;
            $max = -1.0;
            $k = -1;
            for ($i = $a + 1; $i < $b; $i++) {
                [$x, $y] = $ds[$i];
                if ($l2 == 0.0) {
                    $d = ($x - $x1) ** 2 + ($y - $y1) ** 2;
                } else {
                    $t = max(0.0, min(1.0, (($x - $x1) * $dx + ($y - $y1) * $dy) / $l2));
                    $d = ($x - $x1 - $t * $dx) ** 2 + ($y - $y1 - $t * $dy) ** 2;
                }
                if ($d > $max) {
                    $max = $d;
                    $k = $i;
                }
            }
            if ($k > 0 && $max > $tol * $tol) {
                $giu[$k] = true;
                $ngan[] = [$a, $k];
                $ngan[] = [$k, $b];
            }
        }
        $ra = [];
        foreach ($ds as $i => $c) {
            if ($giu[$i]) {
                $ra[] = $c;
            }
        }

        return $ra;
    }

    private static function lamTron(array $ds, int $le): array
    {
        return array_map(fn ($c) => [round((float) $c[0], $le), round((float) $c[1], $le)], $ds);
    }
}
