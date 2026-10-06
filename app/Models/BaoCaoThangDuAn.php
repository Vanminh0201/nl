<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** 1 dòng (1 dự án) trong báo cáo tháng — các cột theo đúng mẫu bảng của Sở. */
class BaoCaoThangDuAn extends Model
{
    protected $table = 'bao_cao_thang_du_an';

    protected $guarded = ['id'];

    protected $casts = [
        'cod_nam' => 'integer',
    ];

    /** Các ô chữ, theo thứ tự cột của mẫu */
    public const TRUONG_CHU = [
        'dia_diem' => 'Địa điểm xây dựng',
        'cong_suat' => 'Công suất (MW)',
        'chu_dau_tu' => 'Chủ đầu tư',
        'phap_ly' => 'Tình trạng pháp lý dự án',
        'tien_do_cod' => 'Tiến độ vận hành COD theo chủ trương đầu tư',
        'tien_do_thuc_te' => 'Tiến độ dự kiến đưa vào vận hành thực tế',
        'tinh_hinh' => 'Tình hình triển khai thực hiện, khó khăn vướng mắc',
        'phuong_an' => 'Phương án xử lý, đề xuất kiến nghị',
        'ghi_chu' => 'Ghi chú',
    ];

    public const KY_COD = [
        'T1' => 'Tháng 1', 'T2' => 'Tháng 2', 'T3' => 'Tháng 3', 'T4' => 'Tháng 4', 'T5' => 'Tháng 5', 'T6' => 'Tháng 6',
        'T7' => 'Tháng 7', 'T8' => 'Tháng 8', 'T9' => 'Tháng 9', 'T10' => 'Tháng 10', 'T11' => 'Tháng 11', 'T12' => 'Tháng 12',
        'Q1' => 'Quý I', 'Q2' => 'Quý II', 'Q3' => 'Quý III', 'Q4' => 'Quý IV',
    ];

    public function baoCao(): BelongsTo
    {
        return $this->belongsTo(BaoCaoThang::class, 'bao_cao_thang_id');
    }

    public function duAn(): BelongsTo
    {
        return $this->belongsTo(DuAn::class, 'du_an_id')->withoutGlobalScope('chu_dau_tu')->withTrashed();
    }

    /** "tháng 6/2027", "Quý IV/2027", "năm 2027" — hoặc chuỗi rỗng khi chưa dự kiến */
    public function codHienThi(): string
    {
        if (! $this->cod_nam) {
            return '';
        }
        $ky = self::KY_COD[$this->cod_ky] ?? null;

        return $ky ? (str_starts_with($this->cod_ky, 'T') ? mb_strtolower($ky) : $ky).'/'.$this->cod_nam : 'năm '.$this->cod_nam;
    }

    /** Số MW đọc từ ô công suất ("48", "28,6", "1.403", "2x900MVA…" → 0 vì không phải MW) để cộng tổng */
    public function congSuatMw(): float
    {
        $s = trim((string) $this->cong_suat);
        if ($s === '' || preg_match('/MVA|km/i', $s)) {
            return 0.0;
        }
        if (! preg_match('/\d[\d.,]*/', $s, $m)) {
            return 0.0;
        }

        return (float) BaoCaoTuanDuAn::chuanHoaSoThapPhan($m[0]);
    }

    /** Nhóm mặc định theo trạng thái dự án khi Sở chưa phân nhóm */
    public static function nhomTheoTrangThai(?string $trangThai): string
    {
        return config('duan.bao_cao_thang.nhom_theo_trang_thai')[$trangThai] ?? 'chuan_bi';
    }

    public function maNhom(): string
    {
        $ds = config('duan.bao_cao_thang.nhom');

        return isset($ds[$this->nhom]) ? $this->nhom : self::nhomTheoTrangThai($this->duAn?->trang_thai);
    }
}
