<?php
function docKmz($duong) {
    $zip = new ZipArchive; $zip->open($duong);
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $n = $zip->getNameIndex($i);
        if (preg_match('/\.kml$/i', $n)) return $zip->getFromIndex($i);
    }
}
function tenCon(DOMElement $el, string $tag): ?string {
    foreach ($el->childNodes as $c) if ($c instanceof DOMElement && $c->localName === $tag) return trim($c->textContent);
    return null;
}
function moTa(DOMElement $el): ?string {
    foreach ($el->childNodes as $c) if ($c instanceof DOMElement && $c->localName === 'description') return trim($c->textContent);
    return null;
}
function loaiHinhHoc(DOMElement $pm): string {
    foreach (['Point', 'LineString', 'Polygon', 'MultiGeometry'] as $tag) {
        if ($pm->getElementsByTagName($tag)->length > 0) return $tag;
    }
    return '?';
}
function duyet(DOMElement $el, array $duongDan, array &$ketQua) {
    foreach ($el->childNodes as $con) {
        if (! $con instanceof DOMElement) continue;
        if ($con->localName === 'Folder' || $con->localName === 'Document') {
            $ten = tenCon($con, 'name') ?? '(khong ten)';
            duyet($con, $con->localName === 'Folder' ? array_merge($duongDan, [$ten]) : $duongDan, $ketQua);
        } elseif ($con->localName === 'Placemark') {
            $ten = tenCon($con, 'name') ?? '(khong ten)';
            $mt = moTa($con);
            $ketQua[] = ['duong_dan' => $duongDan, 'ten' => $ten, 'loai' => loaiHinhHoc($con), 'mo_ta' => $mt];
        } else {
            duyet($con, $duongDan, $ketQua);
        }
    }
}

$kml = docKmz('/home/u371446386/tmp-btwf2.kmz');
$dom = new DOMDocument(); $dom->loadXML($kml);
$ketQua = []; duyet($dom->documentElement, [], $ketQua);
echo 'Tong: '.count($ketQua)."\n\n";

// 1) Liet ke TAT CA ten cua nhom WTG va nhom goc de kiem tra co du 60 khong
echo "=== Tat ca Point (diem), moi noi trong file ===\n";
foreach ($ketQua as $k) {
    if ($k['loai'] === 'Point') {
        echo implode(' > ', $k['duong_dan']).' :: '.$k['ten'].(($k['mo_ta']) ? ' | mota: '.substr(str_replace("\n",' ',$k['mo_ta']),0,80) : '')."\n";
    }
}

echo "\n=== Kiem tra tu khoa TBA/33kV/tram/bien ap trong TEN moi placemark ===\n";
foreach ($ketQua as $k) {
    if (preg_match('/TBA|33\s*kV|tr[aạ]m|bi[eế]n\s*[aá]p|substation/iu', $k['ten'])) {
        echo implode(' > ', $k['duong_dan']).' :: ['.$k['loai'].'] '.$k['ten']."\n";
    }
}

echo "\n=== Kiem tra tu khoa trong MO TA (description) neu co ===\n";
$demMoTa = 0;
foreach ($ketQua as $k) {
    if ($k['mo_ta'] && preg_match('/TBA|33\s*kV|110\s*kV|220\s*kV|tr[aạ]m|bi[eế]n\s*[aá]p/iu', $k['mo_ta'])) {
        $demMoTa++;
        if ($demMoTa <= 10) echo implode(' > ', $k['duong_dan']).' :: '.$k['ten'].' | '.substr(str_replace("\n",' ',$k['mo_ta']),0,100)."\n";
    }
}
echo "Tong so co mo ta khop tu khoa: {$demMoTa}\n";

echo "\n=== Danh sach thu muc goc cap 1 (duoi Document) ===\n";
$capMot = [];
foreach ($ketQua as $k) { $capMot[$k['duong_dan'][0] ?? '(goc)'] = true; }
print_r(array_keys($capMot));
