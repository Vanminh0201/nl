<?php

namespace App\Http\Controllers;

use App\Models\DuAn;
use App\Services\TaoTepExcel;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class XuatDuLieuController extends Controller
{
    /** Các cột của bảng xuất danh mục — dùng chung cho cả CSV và Excel (.xlsx) */
    private const COT = [
        ['ten' => 'TT', 'rong' => 5, 'kieu' => 'o_giua'],
        ['ten' => 'Tên dự án', 'rong' => 26, 'kieu' => 'o_dam'],
        ['ten' => 'Chủ đầu tư', 'rong' => 22, 'kieu' => 'o'],
        ['ten' => 'Loại hình', 'rong' => 14, 'kieu' => 'o'],
        ['ten' => 'Địa điểm trước đây', 'rong' => 34, 'kieu' => 'o'],
        ['ten' => 'Địa điểm hiện nay', 'rong' => 30, 'kieu' => 'o'],
        ['ten' => 'Trạng thái', 'rong' => 18, 'kieu' => 'o'],
        ['ten' => 'Vốn đăng ký (tỷ đồng)', 'rong' => 12, 'kieu' => 'o_giua'],
        ['ten' => 'Diện tích (ha)', 'rong' => 10, 'kieu' => 'o_giua'],
        ['ten' => 'Công suất', 'rong' => 12, 'kieu' => 'o'],
        ['ten' => 'Tiến độ (%)', 'rong' => 9, 'kieu' => 'o_giua'],
        ['ten' => 'Khởi công', 'rong' => 11, 'kieu' => 'o_giua'],
        ['ten' => 'Hoàn thành', 'rong' => 11, 'kieu' => 'o_giua'],
        ['ten' => 'Số văn bản', 'rong' => 14, 'kieu' => 'o'],
        ['ten' => 'Ngày văn bản', 'rong' => 11, 'kieu' => 'o_giua'],
        ['ten' => 'Chủ trương', 'rong' => 8, 'kieu' => 'o_giua'],
        ['ten' => 'Môi trường', 'rong' => 8, 'kieu' => 'o_giua'],
        ['ten' => 'BCNCKT', 'rong' => 8, 'kieu' => 'o_giua'],
        ['ten' => 'Đất đai', 'rong' => 8, 'kieu' => 'o_giua'],
        ['ten' => 'TKKT', 'rong' => 7, 'kieu' => 'o_giua'],
        ['ten' => 'PCCC', 'rong' => 7, 'kieu' => 'o_giua'],
        ['ten' => 'TKBVTC', 'rong' => 8, 'kieu' => 'o_giua'],
        ['ten' => 'Giấy phép XD', 'rong' => 9, 'kieu' => 'o_giua'],
        ['ten' => 'Vĩ độ', 'rong' => 10, 'kieu' => 'o_giua'],
        ['ten' => 'Kinh độ', 'rong' => 10, 'kieu' => 'o_giua'],
        ['ten' => 'Ghi chú', 'rong' => 40, 'kieu' => 'o'],
    ];

    /** 1 dòng dữ liệu của 1 dự án, đúng thứ tự cột COT — dùng chung cho CSV và Excel */
    private static function dongDuLieu(DuAn $d, int $stt): array
    {
        $x = fn ($b) => $b ? 'x' : '';

        return [
            $stt, $d->ten, $d->chu_dau_tu, $d->ten_loai_hinh,
            implode(', ', $d->diaDiemTruocDayDanhSach()), $d->dia_diem, $d->trang_thai,
            $d->von_dang_ky, $d->dien_tich, $d->cong_suat, $d->tien_do,
            $d->ngay_khoi_cong?->format('d/m/Y'), $d->ngay_hoan_thanh?->format('d/m/Y'),
            $d->so_van_ban, $d->ngay_van_ban?->format('d/m/Y'),
            $x($d->pl_chu_truong), $x($d->pl_moi_truong), $x($d->pl_nckt), $x($d->pl_dat_dai),
            $x($d->pl_tkkt), $x($d->pl_pccc), $x($d->pl_tkbvtc), $x($d->pl_xay_dung),
            $d->vi_do, $d->kinh_do,
            $d->ghi_chu,
        ];
    }

    /**
     * Xuất danh mục ra file Excel (.xlsx) thật — dựng thẳng bằng ZipArchive (TaoTepExcel) vì máy
     * chủ không cài được Composer nên không dùng được PhpSpreadsheet. Trước đây nút "Xuất Excel"
     * thực chất xuất CSV (đuôi .csv, không phải .xlsx) nên đổi hẳn sang sinh file Excel thật.
     */
    public function excel(Request $request, TaoTepExcel $excel): BinaryFileResponse
    {
        $dk = $request->only(['tu_khoa', 'loai_hinh', 'trang_thai', 'dia_diem', 'dia_diem_truoc_day', 'pl_chu_truong', 'ho_so']);

        $sheet = $excel->themSheet('Danh mục dự án', array_column(self::COT, 'rong'), 1);
        $excel->dong($sheet, 1, array_column(self::COT, 'ten'), 'dau_cot');

        $stt = 0;
        $dong = 1;
        DuAn::query()->loc($dk)->orderBy('ten')->chunk(500, function ($nhom) use ($excel, $sheet, &$stt, &$dong) {
            foreach ($nhom as $d) {
                $dong++;
                $gt = self::dongDuLieu($d, ++$stt);
                $o = [];
                foreach ($gt as $i => $v) {
                    $o[] = ['v' => $v, 'kieu' => self::COT[$i]['kieu']];
                }
                $excel->dong($sheet, $dong, $o);
            }
        });

        return $excel->taiVe('danh-muc-du-an-cong-nghiep-'.now()->format('Ymd').'.xlsx');
    }

    /**
     * Xuất danh mục ra CSV, đọc thẳng bằng Excel.
     * Ghi BOM UTF-8 để Excel trên Windows không lỗi font tiếng Việt.
     */
    public function csv(Request $request): StreamedResponse
    {
        $dk = $request->only(['tu_khoa', 'loai_hinh', 'trang_thai', 'dia_diem', 'dia_diem_truoc_day', 'pl_chu_truong', 'ho_so']);
        $ten = 'danh-muc-du-an-cong-nghiep-'.now()->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($dk) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_column(self::COT, 'ten'));

            $stt = 0;
            DuAn::query()->loc($dk)->orderBy('ten')->chunk(500, function ($nhom) use ($out, &$stt) {
                foreach ($nhom as $d) {
                    fputcsv($out, self::dongDuLieu($d, ++$stt));
                }
            });

            fclose($out);
        }, $ten, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Xuất GeoJSON để mở bằng QGIS hoặc chồng lên hệ thống GIS của tỉnh */
    public function geojson(Request $request)
    {
        $features = DuAn::query()
            ->loc($request->only(['tu_khoa', 'loai_hinh', 'trang_thai', 'dia_diem', 'dia_diem_truoc_day', 'pl_chu_truong', 'ho_so']))
            ->whereNotNull('vi_do')
            ->get()
            ->map(fn (DuAn $d) => [
                'type' => 'Feature',
                'properties' => [
                    'ten' => $d->ten,
                    'chu_dau_tu' => $d->chu_dau_tu,
                    'loai_hinh' => $d->ten_loai_hinh,
                    'dia_diem' => $d->dia_diem,
                    'dia_diem_truoc_day' => $d->diaDiemTruocDayDanhSach(),
                    'trang_thai' => $d->trang_thai,
                    'von_ty_dong' => $d->von_dang_ky,
                    'dien_tich_ha' => $d->dien_tich,
                    'cong_suat' => $d->cong_suat,
                ],
                'geometry' => ['type' => 'Point', 'coordinates' => [$d->kinh_do, $d->vi_do]],
            ])
            ->values();

        return response()
            ->json(['type' => 'FeatureCollection', 'features' => $features], 200, [
                'Content-Disposition' => 'attachment; filename="du-an-cong-nghiep-'.now()->format('Ymd').'.geojson"',
            ], JSON_UNESCAPED_UNICODE);
    }
}
