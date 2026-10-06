<?php

namespace App\Models;

use App\Support\KyBaoCao;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BaoCaoTuan extends Model
{
    protected $table = 'bao_cao_tuan';

    protected $guarded = ['id'];

    protected $casts = [
        'tu_ngay' => 'date',
        'den_ngay' => 'date',
        'ngay_bao_cao' => 'date',
        'nop_luc' => 'datetime',
        'xu_ly_luc' => 'datetime',
    ];

    public const TRANG_THAI = [
        'nhap' => 'Đang soạn',
        'da_nop' => 'Chờ tiếp nhận',
        'yeu_cau_bo_sung' => 'Yêu cầu bổ sung',
        'da_tiep_nhan' => 'Đã tiếp nhận',
    ];

    public const LOP_CHIP = [
        'nhap' => '',
        'da_nop' => 'xanh',
        'yeu_cau_bo_sung' => 'do',
        'da_tiep_nhan' => 'co',
    ];

    public function nguoiBaoCao(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nguoi_bao_cao_id');
    }

    public function nguoiXuLy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nguoi_xu_ly_id');
    }

    public function chiTiet(): HasMany
    {
        return $this->hasMany(BaoCaoTuanDuAn::class)->orderBy('id');
    }

    public function scopeTrongKy(Builder $q, KyBaoCao $ky): Builder
    {
        return $q->where('nam', $ky->nam)->where('tuan', $ky->tuan);
    }

    /** Bản chưa nộp lần nào là việc riêng của chủ đầu tư — Sở chỉ thấy báo cáo đã gửi đi ít nhất 1 lần. */
    public function scopeDaGui(Builder $q): Builder
    {
        return $q->whereNotNull('nop_luc');
    }

    public function ky(): KyBaoCao
    {
        return KyBaoCao::tuSo($this->nam, $this->tuan);
    }

    public function daTungNop(): bool
    {
        return $this->nop_luc !== null;
    }

    /** Chủ đầu tư được sửa (kể cả sau khi nộp) cho đến khi Sở tiếp nhận. */
    public function conSuaDuoc(): bool
    {
        return $this->trang_thai !== 'da_tiep_nhan';
    }

    public function getTenTrangThaiAttribute(): string
    {
        return self::TRANG_THAI[$this->trang_thai] ?? $this->trang_thai;
    }

    public function getLopChipAttribute(): string
    {
        return self::LOP_CHIP[$this->trang_thai] ?? '';
    }

    /** Mốc thời gian lưu theo UTC (múi giờ máy chủ) — đổi sang giờ Việt Nam khi hiển thị. */
    public function gio(string $cot): ?string
    {
        return $this->{$cot}?->copy()->setTimezone(KyBaoCao::MUI_GIO)->format('d/m/Y H:i');
    }

    public function getTenDonViHienThiAttribute(): string
    {
        return $this->ten_don_vi ?: ($this->nguoiBaoCao?->don_vi ?: ($this->nguoiBaoCao?->name ?? ''));
    }

    /** Dòng "Quảng Trị, ngày … tháng … năm …" dưới tiêu ngữ; chưa chọn ngày thì để trống cho ký tay. */
    public function dongNgayThang(): string
    {
        $n = $this->ngay_bao_cao;

        return ($this->dia_danh ?: config('duan.bao_cao.dia_danh')).', '.($n
            ? 'ngày '.$n->format('d').' tháng '.$n->format('m').' năm '.$n->format('Y')
            : 'ngày      tháng      năm        ');
    }

    /** Phần sau chữ "Số:" — chưa có số thứ tự (VD "/BC-AND2") thì chừa khoảng trống để điền số bằng tay. */
    public function soHieuDeTrong(): string
    {
        $s = preg_replace('/^\s*số\s*:?\s*/iu', '', trim((string) $this->so_hieu)) ?? '';
        if (preg_match('/^\d/', $s)) {
            return $s;
        }

        return str_repeat(' ', 10).($s === '' ? '/BC' : (str_starts_with($s, '/') ? $s : '/'.$s));
    }

    /** Các dòng chức vụ người ký; chủ đầu tư bỏ trống thì dùng mặc định trong cấu hình (KT. GIÁM ĐỐC / PHÓ GIÁM ĐỐC). */
    public function dongChucVuKy(): array
    {
        return BaoCaoTuanDuAn::tachDong($this->chuc_vu_ky ?: config('duan.bao_cao.chuc_vu_ky'));
    }

    public static function soLaMa(int $n): string
    {
        $kq = '';
        foreach (['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40,
            'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1] as $ky => $gt) {
            while ($n >= $gt) {
                $kq .= $ky;
                $n -= $gt;
            }
        }

        return $kq;
    }
}
