<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** 1 tệp văn bản lưu trữ của 1 hồ sơ pháp lý (loai = mã cột pl_… trong DuAn::HO_SO_PHAP_LY) */
class DuAnHoSoTep extends Model
{
    protected $table = 'du_an_ho_so_tep';

    protected $guarded = ['id'];

    protected $casts = [
        'ngay_van_ban' => 'date',
        'kich_thuoc' => 'integer',
    ];

    public function duAn(): BelongsTo
    {
        return $this->belongsTo(DuAn::class);
    }

    public function nguoiTai(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nguoi_tai_id');
    }

    public function kichThuocHienThi(): string
    {
        $kb = $this->kich_thuoc / 1024;

        return $kb >= 1024 ? number_format($kb / 1024, 1, ',', '.').' MB' : number_format(max(1, $kb), 0, ',', '.').' KB';
    }
}
