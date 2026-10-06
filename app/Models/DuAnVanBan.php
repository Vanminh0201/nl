<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Văn bản của 1 hồ sơ pháp lý (ho_so = mã cột pl_… trong DuAn::HO_SO_PHAP_LY): số, ngày, tệp đính kèm.
 * Tệp lưu ở ổ "local" (storage/app/private/van-ban), chỉ mở qua route van-ban.tep có kiểm tra quyền.
 * Tệp cũ còn nằm trong public/du-an-tai-lieu vẫn mở được qua cùng route.
 */
class DuAnVanBan extends Model
{
    protected $table = 'du_an_van_ban';

    protected $guarded = ['id'];

    protected $casts = [
        'ngay_van_ban' => 'date',
        'kich_thuoc' => 'integer',
    ];

    public function duAn()
    {
        return $this->belongsTo(DuAn::class);
    }

    public function getTepUrlAttribute(): ?string
    {
        return $this->tep ? route('van-ban.tep', $this) : null;
    }

    public function getTenHoSoAttribute(): string
    {
        return DuAn::HO_SO_PHAP_LY[$this->ho_so] ?? $this->ho_so;
    }

    public static function luuTep(UploadedFile $tep, int $duAnId): array
    {
        return [
            'tep' => $tep->store('van-ban/'.$duAnId, 'local'),
            'ten_tep' => mb_substr($tep->getClientOriginalName(), 0, 250),
            'kich_thuoc' => (int) $tep->getSize(),
        ];
    }

    /** Đường dẫn tuyệt đối của tệp trên máy chủ (ổ local, hoặc thư mục public với tệp cũ), null nếu không còn */
    public function duongDanTep(): ?string
    {
        if (! $this->tep) {
            return null;
        }
        if (Storage::disk('local')->exists($this->tep)) {
            return Storage::disk('local')->path($this->tep);
        }

        return is_file(public_path($this->tep)) ? public_path($this->tep) : null;
    }

    public function xoaTep(): void
    {
        if ($p = $this->duongDanTep()) {
            @unlink($p);
        }
    }

    public function tenTaiVe(): string
    {
        $duoi = pathinfo($this->ten_tep ?: $this->tep, PATHINFO_EXTENSION);
        if ($this->ten_tep) {
            return $this->ten_tep;
        }

        return ($this->so_van_ban ? str_replace(['/', '\\'], '-', $this->so_van_ban) : 'van-ban').($duoi ? '.'.$duoi : '');
    }
}
