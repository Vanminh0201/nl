<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class BieuTuong extends Model
{
    protected $table = 'bieu_tuong';

    protected $fillable = ['ma', 'ten', 'hinh', 'tep', 'thu_tu'];

    protected $appends = ['tep_url'];

    /** Hình vẽ sẵn cho phép chọn khi không tải file riêng — khớp đối tượng HINH trong public/js/bieu-tuong.js */
    public const HINH_CO_SAN = [
        'diem_moc', 'tram_dien', 'mba', 'may_cat', 'toa_nha', 'kho', 'canh_bao',
        'ccn', 'kcn', 'che_bien', 'vlxd', 'khoang_san', 'thuy_dien', 'dien_gio', 'dien_mat_troi', 'det_may', 'khac',
        'nha_may', 'cang_bien', 'san_bay', 'truong_hoc', 'benh_vien', 'nong_nghiep', 'chan_nuoi', 'thuy_san',
        'khi_dot', 'than', 'logistics', 'vien_thong', 'moi_truong', 'an_ninh', 'nuoc_sach', 'cau_duong', 'tau_hoa',
        'may_tinh', 'ngan_hang', 'sao', 'co',
    ];

    private static ?Collection $boNho = null;

    /** Toàn bộ biểu tượng, xếp theo thứ tự hiển thị, khoá theo mã. Nhớ trong bộ nhớ cho hết vòng đời request. */
    public static function danhSach(): Collection
    {
        return static::$boNho ??= static::query()->orderBy('thu_tu')->orderBy('ten')->get()->keyBy('ma');
    }

    public static function lamMoiBoNho(): void
    {
        static::$boNho = null;
    }

    public function getTepUrlAttribute(): ?string
    {
        return $this->tep ? asset($this->tep) : null;
    }

    /** Đang được loại hình dự án hoặc lớp điểm nào dùng hay không — chặn xoá nếu có */
    public function soDangDung(): int
    {
        return LoaiHinhDuAn::query()->where('bieu_tuong', $this->ma)->count()
            + DuAnHinhHoc::query()->where('bieu_tuong', $this->ma)->count();
    }
}
