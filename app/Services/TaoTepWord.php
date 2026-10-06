<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/**
 * Tạo file .docx tối giản bằng ZipArchive (máy chủ không có Composer để cài PhpWord): chỉ gồm
 * đoạn văn và bảng, font Times New Roman như văn bản hành chính. Cỡ chữ tính theo pt, khoảng
 * cách/lề tính theo cm cho dễ đối chiếu với mẫu Word.
 *
 * Tuỳ chọn đoạn ($tc): can (left|center|right|both), dam, nghieng, gach, co (pt), thut_dau,
 * le_trai, le_phai (cm), truoc, sau (pt), gian_dong (hệ số dòng), vach_tren (kẻ 1 vạch ngang
 * trên đoạn — dùng làm gạch dưới quốc hiệu/tên cơ quan), giu_voi_sau (không tách khỏi đoạn sau).
 */
class TaoTepWord
{
    private const CM = 567; // twip/cm

    private array $than = [];

    public function __construct(private bool $ngang = false, private float $coChu = 13) {}

    /** @param  string|array  $noiDung  chuỗi, hoặc danh sách đoạn chạy [['chu' => .., 'dam' => .., 'nghieng' => .., 'co' => ..], ...] */
    public function doan(string|array $noiDung, array $tc = []): static
    {
        $this->than[] = $this->xmlDoan($noiDung, $tc);

        return $this;
    }

    /**
     * @param  array  $hang  mỗi hàng là danh sách ô; ô là chuỗi (xuống dòng = sang đoạn mới) hoặc
     *                       ['doan' => [[noiDung, tc], ...], 'nen' => 'EEEEEE']
     * @param  array  $rongCot  độ rộng từng cột (cm)
     * @param  array  $tc  vien (mặc định true), so_hang_tieu_de (lặp lại đầu mỗi trang), o (tc mặc định cho ô dạng chuỗi),
     *                     le_o (lề trái/phải trong ô, cm — mặc định 0,15)
     */
    public function bang(array $hang, array $rongCot, array $tc = []): static
    {
        $vien = $tc['vien'] ?? true;
        $soHangTieuDe = $tc['so_hang_tieu_de'] ?? 0;
        $tcO = $tc['o'] ?? [];
        $rong = array_map(fn ($c) => (int) round($c * self::CM), $rongCot);

        $xml = '<w:tbl><w:tblPr><w:tblW w:w="'.array_sum($rong).'" w:type="dxa"/>';
        if ($vien) {
            $xml .= '<w:tblBorders>';
            foreach (['top', 'left', 'bottom', 'right', 'insideH', 'insideV'] as $canh) {
                $xml .= '<w:'.$canh.' w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
            }
            $xml .= '</w:tblBorders>';
        }
        $leO = (int) round(($tc['le_o'] ?? 0.15) * self::CM);
        $xml .= '<w:tblLayout w:type="fixed"/><w:tblCellMar><w:top w:w="40" w:type="dxa"/><w:left w:w="'.$leO.'" w:type="dxa"/>'
            .'<w:bottom w:w="40" w:type="dxa"/><w:right w:w="'.$leO.'" w:type="dxa"/></w:tblCellMar></w:tblPr><w:tblGrid>';
        foreach ($rong as $r) {
            $xml .= '<w:gridCol w:w="'.$r.'"/>';
        }
        $xml .= '</w:tblGrid>';

        foreach ($hang as $i => $cacO) {
            $xml .= '<w:tr>'.($i < $soHangTieuDe ? '<w:trPr><w:tblHeader/></w:trPr>' : '');
            foreach (array_values($cacO) as $j => $o) {
                $xml .= '<w:tc><w:tcPr><w:tcW w:w="'.($rong[$j] ?? 0).'" w:type="dxa"/>';
                if (is_array($o) && ! empty($o['nen'])) {
                    $xml .= '<w:shd w:val="clear" w:color="auto" w:fill="'.$o['nen'].'"/>';
                }
                $xml .= '</w:tcPr>';

                $cacDoan = is_array($o)
                    ? ($o['doan'] ?? [])
                    : array_map(fn ($dong) => [$dong, $tcO], preg_split('/\r\n|\r|\n/', (string) $o));
                foreach ($cacDoan ?: [['', $tcO]] as [$noiDung, $tcDoan]) {
                    $xml .= $this->xmlDoan($noiDung, $tcDoan ?? []);
                }
                $xml .= '</w:tc>';
            }
            $xml .= '</w:tr>';
        }
        $this->than[] = $xml.'</w:tbl>';

        return $this;
    }

    public function taiVe(string $tenTep): BinaryFileResponse
    {
        return response()
            ->download($this->luuTam(), $tenTep, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])
            ->deleteFileAfterSend(true);
    }

    public function luuTam(): string
    {
        $duongDan = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new ZipArchive;
        $zip->open($duongDan, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            .'</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            .'</Relationships>');
        $zip->addFromString('word/_rels/document.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>');
        $zip->addFromString('word/styles.xml', $this->xmlKieu());
        $zip->addFromString('word/document.xml', $this->xmlVanBan());
        $zip->close();

        return $duongDan;
    }

    private function xmlVanBan(): string
    {
        // A4, lề trên/dưới/phải 2cm, trái 3cm như mẫu; khổ ngang dùng lề 2cm đều 4 cạnh
        $trang = $this->ngang
            ? '<w:pgSz w:w="16838" w:h="11906" w:orient="landscape"/><w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134" w:header="720" w:footer="720" w:gutter="0"/>'
            : '<w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1701" w:header="720" w:footer="720" w:gutter="0"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><w:body>'
            .implode('', $this->than)
            .'<w:sectPr>'.$trang.'</w:sectPr></w:body></w:document>';
    }

    private function xmlKieu(): string
    {
        $co = (int) round($this->coChu * 2);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:docDefaults><w:rPrDefault><w:rPr>'
            .'<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:eastAsia="Times New Roman" w:cs="Times New Roman"/>'
            .'<w:sz w:val="'.$co.'"/><w:szCs w:val="'.$co.'"/><w:lang w:val="vi-VN" w:eastAsia="en-US" w:bidi="ar-SA"/>'
            .'</w:rPr></w:rPrDefault><w:pPrDefault><w:pPr><w:spacing w:after="0" w:line="240" w:lineRule="auto"/></w:pPr></w:pPrDefault></w:docDefaults>'
            .'<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/><w:qFormat/></w:style>'
            .'<w:style w:type="table" w:default="1" w:styleId="TableNormal"><w:name w:val="Normal Table"/><w:uiPriority w:val="99"/><w:semiHidden/>'
            .'<w:tblPr><w:tblInd w:w="0" w:type="dxa"/><w:tblCellMar><w:top w:w="0" w:type="dxa"/><w:left w:w="108" w:type="dxa"/>'
            .'<w:bottom w:w="0" w:type="dxa"/><w:right w:w="108" w:type="dxa"/></w:tblCellMar></w:tblPr></w:style>'
            .'</w:styles>';
    }

    private function xmlDoan(string|array $noiDung, array $tc): string
    {
        $pPr = '';
        if (! empty($tc['giu_voi_sau'])) {
            $pPr .= '<w:keepNext/>';
        }
        if (! empty($tc['vach_tren'])) {
            $pPr .= '<w:pBdr><w:top w:val="single" w:sz="6" w:space="1" w:color="000000"/></w:pBdr>';
        }
        $pPr .= '<w:spacing w:before="'.(int) round(($tc['truoc'] ?? 0) * 20).'" w:after="'.(int) round(($tc['sau'] ?? 0) * 20).'"'
            .' w:line="'.(int) round(240 * ($tc['gian_dong'] ?? 1)).'" w:lineRule="auto"/>';
        if (isset($tc['le_trai']) || isset($tc['le_phai']) || isset($tc['thut_dau'])) {
            $pPr .= '<w:ind w:left="'.(int) round(($tc['le_trai'] ?? 0) * self::CM).'" w:right="'.(int) round(($tc['le_phai'] ?? 0) * self::CM).'"'
                .(isset($tc['thut_dau']) ? ' w:firstLine="'.(int) round($tc['thut_dau'] * self::CM).'"' : '').'/>';
        }
        if (! empty($tc['can'])) {
            $pPr .= '<w:jc w:val="'.$tc['can'].'"/>';
        }
        // Cỡ chữ của dấu đoạn quyết định chiều cao dòng trống — cần khi đoạn chỉ dùng để kẻ vạch
        if (isset($tc['co'])) {
            $co = (int) round($tc['co'] * 2);
            $pPr .= '<w:rPr><w:sz w:val="'.$co.'"/><w:szCs w:val="'.$co.'"/></w:rPr>';
        }

        $cacDoanChay = is_array($noiDung) ? $noiDung : [['chu' => $noiDung]];
        $r = '';
        foreach ($cacDoanChay as $chay) {
            if (($chay['chu'] ?? '') === '') {
                continue;
            }
            $r .= '<w:r>'.$this->xmlDinhDangChu($chay + $tc).$this->xmlChu($chay['chu']).'</w:r>';
        }

        return '<w:p><w:pPr>'.$pPr.'</w:pPr>'.$r.'</w:p>';
    }

    private function xmlDinhDangChu(array $tc): string
    {
        $x = '';
        if (! empty($tc['dam'])) {
            $x .= '<w:b/><w:bCs/>';
        }
        if (! empty($tc['nghieng'])) {
            $x .= '<w:i/><w:iCs/>';
        }
        if (isset($tc['co'])) {
            $co = (int) round($tc['co'] * 2);
            $x .= '<w:sz w:val="'.$co.'"/><w:szCs w:val="'.$co.'"/>';
        }
        if (! empty($tc['gach'])) {
            $x .= '<w:u w:val="single"/>';
        }

        return $x === '' ? '' : '<w:rPr>'.$x.'</w:rPr>';
    }

    private function xmlChu(string $chu): string
    {
        // Ký tự điều khiển (dán từ Excel/PDF) làm hỏng XML khiến Word báo file lỗi
        $chu = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $chu) ?? '';
        $cacDong = preg_split('/\r\n|\r|\n/', $chu);

        return implode('<w:br/>', array_map(
            fn ($d) => implode('<w:tab/>', array_map(
                fn ($p) => '<w:t xml:space="preserve">'.htmlspecialchars($p, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</w:t>',
                explode("\t", $d)
            )),
            $cacDong
        ));
    }
}
