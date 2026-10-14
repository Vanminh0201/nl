<?php

namespace App\Models;

use App\Support\KyThang;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nội dung Sở biên tập cho 1 dự án trong bảng tổng hợp tháng (nhóm theo xã). Ưu tiên hơn báo cáo của chủ
 * đầu tư cùng tháng; dự án chưa có chủ đầu tư báo cáo thì Sở tự ghi ở đây.
 */
class BaoCaoThangTongHop extends Model
{
    protected $table = 'bao_cao_thang_tong_hop';

    protected $guarded = ['id'];

    public function duAn(): BelongsTo
    {
        return $this->belongsTo(DuAn::class, 'du_an_id')->withoutGlobalScope('chu_dau_tu')->withTrashed();
    }

    public function nguoiSua(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nguoi_sua_id');
    }

    public function scopeTrongKy(Builder $q, KyThang $ky): Builder
    {
        return $q->where('nam', $ky->nam)->where('thang', $ky->thang);
    }

    public function ky(): KyThang
    {
        return KyThang::tuSo($this->nam, $this->thang);
    }

    /** @return array<string, ?string> 6 cột nội dung của mẫu */
    public function noiDungMau(): array
    {
        return collect(BaoCaoThangDuAn::NOI_DUNG_MAU)->mapWithKeys(fn ($nhan, $cot) => [$cot => BaoCaoThangDuAn::chu($this->{$cot})])->all();
    }
}
