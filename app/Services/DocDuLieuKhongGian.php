<?php

namespace App\Services;

use RuntimeException;
use SimpleXMLElement;

/**
 * Đọc file GeoJSON, KML, KMZ thành mảng feature chuẩn GeoJSON.
 * Không phụ thuộc thư viện ngoài: dùng ZipArchive và SimpleXML của PHP.
 */
class DocDuLieuKhongGian
{
    /** @return array<int, array> danh sách feature */
    public function doc(string $duongDan, string $tenGoc): array
    {
        $duoi = strtolower(pathinfo($tenGoc, PATHINFO_EXTENSION));

        return match ($duoi) {
            'kmz' => $this->docKmz($duongDan),
            'kml' => $this->docKml(file_get_contents($duongDan)),
            'geojson', 'json' => $this->docGeoJson(file_get_contents($duongDan)),
            default => throw new RuntimeException('Chỉ hỗ trợ file GeoJSON, KML và KMZ.'),
        };
    }

    protected function docKmz(string $duongDan): array
    {
        if (! class_exists(\ZipArchive::class)) {
            throw new RuntimeException('Máy chủ chưa bật extension zip của PHP, không giải nén được KMZ.');
        }

        $zip = new \ZipArchive;
        if ($zip->open($duongDan) !== true) {
            throw new RuntimeException('Không mở được file KMZ.');
        }

        $noiDung = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $ten = $zip->getNameIndex($i);
            if (preg_match('/\.kml$/i', $ten)) {
                $noiDung = $zip->getFromIndex($i);
                break;
            }
        }
        $zip->close();

        if ($noiDung === null) {
            throw new RuntimeException('File KMZ không chứa file KML nào.');
        }

        return $this->docKml($noiDung);
    }

    protected function docGeoJson(string $noiDung): array
    {
        $j = json_decode($noiDung, true);
        if (! is_array($j)) {
            throw new RuntimeException('File GeoJSON không hợp lệ.');
        }

        return match ($j['type'] ?? null) {
            'FeatureCollection' => $j['features'] ?? [],
            'Feature' => [$j],
            null => throw new RuntimeException('File GeoJSON thiếu trường type.'),
            default => [['type' => 'Feature', 'properties' => [], 'geometry' => $j]],
        };
    }

    protected function docKml(string $noiDung): array
    {
        $truoc = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($noiDung);
        libxml_use_internal_errors($truoc);

        if ($xml === false) {
            throw new RuntimeException('File KML không đọc được, có thể bị lỗi định dạng XML.');
        }

        $features = [];
        foreach ($xml->xpath('//*[local-name()="Placemark"]') ?: [] as $pm) {
            $thuocTinh = $this->thuocTinhPlacemark($pm);

            // 1 Placemark chỉ có 1 hình học theo chuẩn KML, nhưng hình học đó có thể là
            // MultiGeometry gộp nhiều Point/LineString/Polygon con (VD: 1 tuyến dây vẽ thành
            // nhiều đoạn rời, hoặc 1 cụm điểm) — xpath ".//" bên dưới sẽ khớp mọi phần tử con
            // dù có nằm trong MultiGeometry hay không, nên gộp chúng lại thành 1 feature dạng
            // Multi* để giữ đúng "1 Placemark = 1 đối tượng" như file gốc, thay vì tách rời
            // từng phần tử con thành nhiều đối tượng riêng.
            $diem = [];
            foreach ($pm->xpath('.//*[local-name()="Point"]') ?: [] as $el) {
                $toa = $this->toaDo($el);
                if ($toa) {
                    $diem[] = $toa[0];
                }
            }
            if (count($diem) === 1) {
                $features[] = $this->feature($thuocTinh, ['type' => 'Point', 'coordinates' => $diem[0]]);
            } elseif (count($diem) > 1) {
                $features[] = $this->feature($thuocTinh, ['type' => 'MultiPoint', 'coordinates' => $diem]);
            }

            $duong = [];
            foreach ($pm->xpath('.//*[local-name()="LineString"]') ?: [] as $el) {
                $toa = $this->toaDo($el);
                if (count($toa) > 1) {
                    $duong[] = $toa;
                }
            }
            if (count($duong) === 1) {
                $features[] = $this->feature($thuocTinh, ['type' => 'LineString', 'coordinates' => $duong[0]]);
            } elseif (count($duong) > 1) {
                $features[] = $this->feature($thuocTinh, ['type' => 'MultiLineString', 'coordinates' => $duong]);
            }

            $vung = [];
            foreach ($pm->xpath('.//*[local-name()="Polygon"]') ?: [] as $el) {
                $vongEl = $el->xpath('.//*[local-name()="outerBoundaryIs"]//*[local-name()="coordinates"]')
                    ?: $el->xpath('.//*[local-name()="coordinates"]');
                if (! $vongEl) {
                    continue;
                }
                $toa = $this->tachToaDo((string) $vongEl[0]);
                if (count($toa) > 2) {
                    $vung[] = [$toa];
                }
            }
            if (count($vung) === 1) {
                $features[] = $this->feature($thuocTinh, ['type' => 'Polygon', 'coordinates' => $vung[0]]);
            } elseif (count($vung) > 1) {
                $features[] = $this->feature($thuocTinh, ['type' => 'MultiPolygon', 'coordinates' => $vung]);
            }
        }

        return $features;
    }

    protected function thuocTinhPlacemark(SimpleXMLElement $pm): array
    {
        $lay = function (string $ten) use ($pm) {
            $n = $pm->xpath('./*[local-name()="'.$ten.'"]');

            return $n ? trim((string) $n[0]) : '';
        };

        $tt = ['name' => $lay('name'), 'description' => $lay('description')];

        foreach ($pm->xpath('.//*[local-name()="Data"]') ?: [] as $d) {
            $k = (string) $d['name'];
            $v = $d->xpath('./*[local-name()="value"]');
            if ($k !== '' && $v) {
                $tt[$k] = trim((string) $v[0]);
            }
        }
        foreach ($pm->xpath('.//*[local-name()="SimpleData"]') ?: [] as $d) {
            $k = (string) $d['name'];
            if ($k !== '') {
                $tt[$k] = trim((string) $d);
            }
        }

        // Bản xuất OpenStreetMap thường nhét thẻ vào phần description
        if (empty($tt['voltage']) && preg_match('/voltage[^0-9]{0,6}(\d{3,7})/i', $tt['description'], $m)) {
            $tt['voltage'] = $m[1];
        }
        if (empty($tt['power']) && preg_match('/power[^a-z]{0,6}(substation|plant|line|minor_line|tower|generator)/i', $tt['description'], $m)) {
            $tt['power'] = strtolower($m[1]);
        }

        return $tt;
    }

    protected function toaDo(SimpleXMLElement $el): array
    {
        $c = $el->xpath('.//*[local-name()="coordinates"]');

        return $c ? $this->tachToaDo((string) $c[0]) : [];
    }

    /** KML ghi theo thứ tự kinh độ, vĩ độ, cao độ */
    protected function tachToaDo(string $chuoi): array
    {
        $ds = [];
        foreach (preg_split('/\s+/', trim($chuoi)) as $cap) {
            if ($cap === '') {
                continue;
            }
            $p = explode(',', $cap);
            if (count($p) < 2) {
                continue;
            }
            $lng = (float) $p[0];
            $lat = (float) $p[1];
            if (is_finite($lng) && is_finite($lat) && abs($lat) <= 90 && abs($lng) <= 180) {
                $ds[] = [$lng, $lat];
            }
        }

        return $ds;
    }

    protected function feature(array $thuocTinh, array $hinhHoc): array
    {
        return ['type' => 'Feature', 'properties' => $thuocTinh, 'geometry' => $hinhHoc];
    }
}
