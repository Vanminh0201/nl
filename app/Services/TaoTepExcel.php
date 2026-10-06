<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/**
 * Dựng file Excel (.xlsx) tối giản bằng ZipArchive — máy chủ không cài được Composer nên không dùng
 * PhpSpreadsheet. Đủ cho bảng báo cáo in ấn: nhiều sheet, font Times New Roman, viền, tô nền, gộp ô,
 * độ rộng cột, lặp dòng tiêu đề khi in, khổ A4 ngang vừa 1 trang bề ngang.
 *
 * Chiều cao dòng dữ liệu để trống cho Excel/LibreOffice tự giãn theo nội dung (ô có xuống dòng) —
 * chỉ dòng gộp ô (Excel không tự giãn) mới đặt chiều cao ước lượng.
 */
class TaoTepExcel
{
    /** Tên kiểu ô → chỉ số cellXfs trong styles.xml */
    public const KIEU = [
        'thuong' => 0, 'tieu_de' => 1, 'tieu_de_phu' => 2, 'dau_cot' => 3, 'nhom' => 4, 'nhom_giua' => 5,
        'o' => 6, 'o_giua' => 7, 'o_dam' => 8, 'ghi' => 9, 'ghi_dam' => 10, 'nghieng' => 11,
    ];

    private array $sheet = [];

    /**
     * @param  float[]  $rong  độ rộng từng cột (đơn vị ký tự như Excel)
     * @param  int|null  $dongLap  số dòng tiêu đề cột lặp lại ở đầu mỗi trang in (và cố định khi cuộn)
     */
    public function themSheet(string $ten, array $rong, ?int $dongLap = null): int
    {
        $ten = $this->tenSheetHopLe($ten);
        $this->sheet[] = ['ten' => $ten, 'rong' => array_values($rong), 'dong' => [], 'gop' => [], 'lap' => $dongLap];

        return count($this->sheet) - 1;
    }

    /**
     * Ghi 1 dòng. $o: mảng theo cột (từ cột A), mỗi phần tử là chuỗi hoặc ['v' => …, 'kieu' => …];
     * null = ô trống (vẫn kẻ viền nếu truyền $kieuMacDinh có viền).
     */
    public function dong(int $sheet, int $soDong, array $o, string $kieuMacDinh = 'o', ?float $cao = null): void
    {
        $this->sheet[$sheet]['dong'][$soDong] = ['o' => $o, 'kieu' => $kieuMacDinh, 'cao' => $cao];
    }

    /** Gộp ô, VD gop(0, 2, 1, 2, 10) = A2:J2 */
    public function gop(int $sheet, int $dong1, int $cot1, int $dong2, int $cot2): void
    {
        if ($dong1 === $dong2 && $cot1 === $cot2) {
            return;
        }
        $this->sheet[$sheet]['gop'][] = self::o($cot1, $dong1).':'.self::o($cot2, $dong2);
    }

    /** Ước lượng chiều cao (pt) cho ô gộp chứa đoạn chữ dài — Excel không tự giãn dòng có ô gộp */
    public static function caoUocLuong(string $chu, float $rongKyTu, float $coChu = 12): float
    {
        $soDong = 0;
        foreach (preg_split('/\r\n|\r|\n/', $chu) as $doan) {
            $soDong += max(1, (int) ceil(mb_strlen($doan) / max(1, $rongKyTu * 1.05)));
        }

        return round($soDong * $coChu * 1.32 + 4, 2);
    }

    public function luu(string $duongDan): void
    {
        if (! $this->sheet) {
            $this->themSheet('Sheet1', [10]);
        }
        $zip = new ZipArchive;
        if ($zip->open($duongDan, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Không tạo được file Excel.');
        }

        $n = count($this->sheet);
        $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        for ($i = 1; $i <= $n; $i++) {
            $ct .= '<Override PartName="/xl/worksheets/sheet'.$i.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        $zip->addFromString('[Content_Types].xml', $ct.'</Types>');

        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>');

        $sheets = $rels = $ten = '';
        foreach ($this->sheet as $i => $s) {
            $sheets .= '<sheet name="'.self::xml($s['ten']).'" sheetId="'.($i + 1).'" r:id="rId'.($i + 1).'"/>';
            $rels .= '<Relationship Id="rId'.($i + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.($i + 1).'.xml"/>';
            if ($s['lap']) {
                $ten .= '<definedName name="_xlnm.Print_Titles" localSheetId="'.$i.'">\''.self::xml(str_replace("'", "''", $s['ten'])).'\'!$'.$s['lap'].':$'.$s['lap'].'</definedName>';
            }
            $zip->addFromString('xl/worksheets/sheet'.($i + 1).'.xml', $this->xmlSheet($s));
        }
        $rels .= '<Relationship Id="rId'.($n + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<bookViews><workbookView/></bookViews><sheets>'.$sheets.'</sheets>'
            .($ten ? '<definedNames>'.$ten.'</definedNames>' : '').'</workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$rels.'</Relationships>');
        $zip->addFromString('xl/styles.xml', $this->xmlStyles());
        $zip->close();
    }

    public function taiVe(string $tenTep): BinaryFileResponse
    {
        $tam = tempnam(sys_get_temp_dir(), 'xlsx');
        $this->luu($tam);

        return response()->download($tam, $tenTep, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    private function xmlSheet(array $s): string
    {
        $soCot = count($s['rong']);
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>'
            .'<sheetViews><sheetView workbookViewId="0" zoomScale="85" zoomScaleNormal="85">'
            .($s['lap'] ? '<pane ySplit="'.$s['lap'].'" topLeftCell="A'.($s['lap'] + 1).'" activePane="bottomLeft" state="frozen"/>' : '')
            .'</sheetView></sheetViews><sheetFormatPr defaultRowHeight="15.75"/><cols>';
        foreach ($s['rong'] as $i => $w) {
            $x .= '<col min="'.($i + 1).'" max="'.($i + 1).'" width="'.$w.'" customWidth="1"/>';
        }
        $x .= '</cols><sheetData>';

        ksort($s['dong']);
        foreach ($s['dong'] as $r => $d) {
            $x .= '<row r="'.$r.'"'.($d['cao'] ? ' ht="'.$d['cao'].'" customHeight="1"' : '').'>';
            for ($c = 1; $c <= max($soCot, count($d['o'])); $c++) {
                $o = $d['o'][$c - 1] ?? null;
                $kieu = is_array($o) ? ($o['kieu'] ?? $d['kieu']) : $d['kieu'];
                $gt = is_array($o) ? ($o['v'] ?? null) : $o;
                $s1 = self::KIEU[$kieu] ?? 0;
                $ref = self::o($c, $r);
                if ($gt === null || $gt === '') {
                    // Ô trống vẫn ghi kiểu để giữ viền/nền của bảng
                    $x .= $s1 ? '<c r="'.$ref.'" s="'.$s1.'"/>' : '';
                } elseif (is_int($gt) || is_float($gt)) {
                    $x .= '<c r="'.$ref.'" s="'.$s1.'"><v>'.$gt.'</v></c>';
                } else {
                    $x .= '<c r="'.$ref.'" s="'.$s1.'" t="inlineStr"><is><t xml:space="preserve">'.self::xml((string) $gt).'</t></is></c>';
                }
            }
            $x .= '</row>';
        }
        $x .= '</sheetData>';
        if ($s['gop']) {
            $x .= '<mergeCells count="'.count($s['gop']).'">';
            foreach ($s['gop'] as $g) {
                $x .= '<mergeCell ref="'.$g.'"/>';
            }
            $x .= '</mergeCells>';
        }

        return $x.'<printOptions horizontalCentered="1"/>'
            .'<pageMargins left="0.3" right="0.3" top="0.4" bottom="0.4" header="0.2" footer="0.2"/>'
            .'<pageSetup paperSize="9" orientation="landscape" fitToWidth="1" fitToHeight="0"/>'
            .'<headerFooter><oddFooter>&amp;CTrang &amp;P/&amp;N</oddFooter></headerFooter>'
            .'</worksheet>';
    }

    private function xmlStyles(): string
    {
        $font = fn (int $co, bool $dam = false, bool $nghieng = false) => '<font>'.($dam ? '<b/>' : '').($nghieng ? '<i/>' : '')
            .'<sz val="'.$co.'"/><name val="Times New Roman"/><family val="1"/></font>';
        $can = fn (string $ngang, string $doc, bool $xuongDong = true) => '<alignment horizontal="'.$ngang.'" vertical="'.$doc.'"'
            .($xuongDong ? ' wrapText="1"' : '').'/>';
        // fontId, fillId, borderId, căn lề — đúng thứ tự hằng KIEU ở trên
        $xf = [
            [0, 0, 0, null],
            [2, 0, 0, $can('center', 'center')],
            [4, 0, 0, $can('center', 'center')],
            [1, 2, 1, $can('center', 'center')],
            [1, 3, 1, $can('left', 'center')],
            [1, 3, 1, $can('center', 'center')],
            [0, 0, 1, $can('left', 'top')],
            [0, 0, 1, $can('center', 'top')],
            [1, 0, 1, $can('left', 'top')],
            [0, 0, 0, $can('left', 'top')],
            [1, 0, 0, $can('left', 'top')],
            [3, 0, 0, $can('center', 'center')],
        ];
        $cellXfs = '';
        foreach ($xf as [$f, $fill, $b, $a]) {
            $cellXfs .= '<xf numFmtId="0" fontId="'.$f.'" fillId="'.$fill.'" borderId="'.$b.'" xfId="0"'
                .($f ? ' applyFont="1"' : '').($fill ? ' applyFill="1"' : '').($b ? ' applyBorder="1"' : '')
                .($a ? ' applyAlignment="1">'.$a.'</xf>' : '/>');
        }
        $vien = '<left style="thin"><color auto="1"/></left><right style="thin"><color auto="1"/></right>'
            .'<top style="thin"><color auto="1"/></top><bottom style="thin"><color auto="1"/></bottom><diagonal/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="5">'.$font(12).$font(12, true).$font(14, true).$font(12, false, true).$font(13, true).'</fonts>'
            .'<fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFE7E6E6"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFF2F2F2"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border>'.$vien.'</border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="'.count($xf).'">'.$cellXfs.'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }

    /** Toạ độ ô kiểu A1 */
    public static function o(int $cot, int $dong): string
    {
        $chu = '';
        while ($cot > 0) {
            $m = ($cot - 1) % 26;
            $chu = chr(65 + $m).$chu;
            $cot = intdiv($cot - $m - 1, 26);
        }

        return $chu.$dong;
    }

    private function tenSheetHopLe(string $ten): string
    {
        $ten = mb_substr(trim(preg_replace('/[\[\]:*?\/\\\\]/u', ' ', $ten)), 0, 31) ?: 'Sheet';
        $goc = $ten;
        $dem = 2;
        while (collect($this->sheet)->contains(fn ($s) => mb_strtolower($s['ten']) === mb_strtolower($ten))) {
            $ten = mb_substr($goc, 0, 28).' '.$dem++;
        }

        return $ten;
    }

    private static function xml(string $s): string
    {
        // Bỏ ký tự điều khiển không hợp lệ trong XML (hay lẫn khi dán từ Word) rồi mới escape
        $s = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $s) ?? '';

        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
