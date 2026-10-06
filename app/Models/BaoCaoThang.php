<?php

namespace App\Models;

use App\Support\KyBaoCao;
use App\Support\KyThang;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Báo cáo tháng của 1 chủ đầu tư — cùng quy trình với báo cáo tuần: nháp → gửi → Sở tiếp nhận/yêu cầu bổ sung. */
class BaoCaoThang extends Model
{
    protected $table = 'bao_cao_thang';

    protected $guarded = ['id'];

    protected $casts = [
        'nop_luc' => 'datetime',
        'xu_ly_luc' => 'datetime',
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
        return $this->hasMany(BaoCaoThangDuAn::class)->orderBy('id');
    }

    public function scopeTrongKy(Builder $q, KyThang $ky): Builder
    {
        return $q->where('nam', $ky->nam)->where('thang', $ky->thang);
    }

    public function scopeDaGui(Builder $q): Builder
    {
        return $q->whereNotNull('nop_luc');
    }

    public function ky(): KyThang
    {
        return KyThang::tuSo($this->nam, $this->thang);
    }

    public function daTungNop(): bool
    {
        return $this->nop_luc !== null;
    }

    public function conSuaDuoc(): bool
    {
        return $this->trang_thai !== 'da_tiep_nhan';
    }

    public function getTenTrangThaiAttribute(): string
    {
        return BaoCaoTuan::TRANG_THAI[$this->trang_thai] ?? $this->trang_thai;
    }

    public function getLopChipAttribute(): string
    {
        return BaoCaoTuan::LOP_CHIP[$this->trang_thai] ?? '';
    }

    public function gio(string $cot): ?string
    {
        return $this->{$cot}?->copy()->setTimezone(KyBaoCao::MUI_GIO)->format('d/m/Y H:i');
    }

    public function getTenDonViHienThiAttribute(): string
    {
        return $this->ten_don_vi ?: ($this->nguoiBaoCao?->don_vi ?: ($this->nguoiBaoCao?->name ?? ''));
    }
}
