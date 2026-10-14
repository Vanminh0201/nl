<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 1 dòng (1 dự án) trong báo cáo tháng — các cột theo mẫu "Tình hình triển khai các dự án năng lượng trên địa bàn
 * tỉnh". Báo cáo gửi trước khi đổi mẫu chỉ có cột cũ (pháp lý, tiến độ COD, phương án…): noiDungMau() quy đổi sang mẫu mới.
 */
class BaoCaoThangDuAn extends Model
{
    protected $table = 'bao_cao_thang_du_an';

    protected $guarded = ['id'];

    protected $casts = [
        'cod_nam' => 'integer',
    ];

    /** 6 cột nội dung của mẫu (sau cột "Thông tin dự án"), theo thứ tự cột */
    public const NOI_DUNG_MAU = [
        'du_kien_khoi_cong' => 'Dự kiến khởi công',
        'du_kien_hoan_thanh' => 'Dự kiến hoàn thành',
        'tinh_hinh' => 'Tình hình thực hiện',
        'kho_khan' => 'Khó khăn vướng mắc',
        'kien_nghi_xa' => 'Kiến nghị xử lý — Xã/phường',
        'kien_nghi_so' => 'Kiến nghị xử lý — Sở, ngành',
    ];

    /** Các ô chủ đầu tư nhập cho mỗi dự án */
    public const TRUONG_CHU = [
        'cong_suat' => 'Công suất (MW)',
        'chu_dau_tu' => 'Chủ đầu tư',
    ] + self::NOI_DUNG_MAU;

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

    /**
     * 6 cột nội dung theo mẫu. Dòng của báo cáo gửi theo mẫu cũ: "Phương án xử lý" sang "Kiến nghị — Sở, ngành",
     * mốc COD / tiến độ COD sang "Dự kiến hoàn thành" (ô "Tình hình" cũ đã gồm cả khó khăn vướng mắc).
     *
     * @return array<string, ?string>
     */
    public function noiDungMau(): array
    {
        $gt = collect(self::NOI_DUNG_MAU)->mapWithKeys(fn ($nhan, $cot) => [$cot => self::chu($this->{$cot})])->all();
        $gt['du_kien_hoan_thanh'] ??= self::chu($this->codHienThi() ? 'Dự kiến COD: '.$this->codHienThi() : $this->tien_do_cod);
        $gt['kien_nghi_so'] ??= self::chu($this->phuong_an);

        return $gt;
    }

    /** Chuỗi đã cắt khoảng trắng, rỗng thì null */
    public static function chu(mixed $s): ?string
    {
        $s = trim((string) $s);

        return $s === '' ? null : $s;
    }
}
