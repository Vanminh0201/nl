<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LopHaTang extends Model
{
    protected $table = 'lop_ha_tang';

    protected $guarded = ['id'];

    protected $casts = ['hien_thi' => 'boolean'];

    public function doiTuong(): HasMany
    {
        return $this->hasMany(DoiTuongHaTang::class, 'lop_id');
    }

    public function nguoiTao()
    {
        return $this->belongsTo(User::class, 'nguoi_tao_id');
    }
}
