<?php

namespace App\Models;

use App\Support\KyThang;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Sản lượng điện 1 tháng của 1 dự án (kWh) — chủ đầu tư báo cáo hoặc Sở nhập thay */
class SanLuongThang extends Model
{
    protected $table = 'san_luong_thang';

    protected $guarded = ['id'];

    protected $casts = [
        'san_luong' => 'float',
        'gui_luc' => 'datetime',
        'xu_ly_luc' => 'datetime',
    ];

    public const NGUON = ['chu_dau_tu' => 'Chủ đầu tư báo cáo', 'so' => 'Sở nhập'];

    /** Chủ đầu tư gửi thì khoá, chờ Sở tiếp nhận; Sở trả lại thì chủ đầu tư mới sửa, gửi lại được */
    public const TRANG_THAI = ['nhap' => 'Đang soạn', 'da_gui' => 'Chờ tiếp nhận', 'da_tiep_nhan' => 'Đã tiếp nhận', 'tra_lai' => 'Sở trả lại'];

    /** Số liệu chính thức (vào tổng hợp, thống kê): đã gửi chờ tiếp nhận hoặc đã tiếp nhận */
    public const CHINH_THUC = ['da_gui', 'da_tiep_nhan'];

    public function duAn(): BelongsTo
    {
        return $this->belongsTo(DuAn::class);
    }

    public function nguoiCapNhat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nguoi_cap_nhat_id');
    }

    public function nguoiXuLy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nguoi_xu_ly_id');
    }

    public function scopeChinhThuc(Builder $q): Builder
    {
        return $q->whereIn('trang_thai', self::CHINH_THUC)->whereNotNull('san_luong');
    }

    public function scopeTrongKy(Builder $q, KyThang $ky): Builder
    {
        return $q->where('nam', $ky->nam)->where('thang', $ky->thang);
    }

    public function ky(): KyThang
    {
        return KyThang::tuSo($this->nam, $this->thang);
    }

    /** Đã có số liệu chính thức: đã gửi (chờ tiếp nhận) hoặc đã tiếp nhận; bản nháp, bản bị trả lại chưa tính */
    public function daCo(): bool
    {
        return in_array($this->trang_thai, self::CHINH_THUC, true) && $this->san_luong !== null;
    }

    /** Chủ đầu tư không sửa được: đã gửi hoặc Sở đã tiếp nhận (chỉ sửa được khi Sở trả lại) */
    public function khoa(): bool
    {
        return in_array($this->trang_thai, self::CHINH_THUC, true);
    }

    public function getTenAttribute(): string
    {
        return 'Sản lượng tháng '.$this->thang.'/'.$this->nam.' – '.($this->duAn?->ten ?? '#'.$this->du_an_id);
    }
}
