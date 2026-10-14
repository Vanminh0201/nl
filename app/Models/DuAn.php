<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DuAn extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'du_an';

    protected $guarded = ['id'];

    protected $casts = [
        'ngay_khoi_cong' => 'date',
        'ngay_hoan_thanh' => 'date',
        'ngay_van_ban' => 'date',
        'dia_diem_hien_nay' => 'array',
        'dia_diem_truoc_day' => 'array',
        'pl_chu_truong' => 'boolean',
        'pl_dat_dai' => 'boolean',
        'pl_moi_truong' => 'boolean',
        'pl_pccc' => 'boolean',
        'pl_xay_dung' => 'boolean',
        'pl_nckt' => 'boolean',
        'pl_tkkt' => 'boolean',
        'pl_tkbvtc' => 'boolean',
        'vi_do' => 'float',
        'kinh_do' => 'float',
        'von_dang_ky' => 'float',
        'dien_tich' => 'float',
        'cong_suat_mw' => 'float',
        'san_luong_dien' => 'float',
        'tien_do' => 'float',
    ];

    /** Hồ sơ pháp lý, xếp theo trình tự thủ tục đầu tư xây dựng (dùng chung cho form, chi tiết, thống kê) */
    public const HO_SO_PHAP_LY = [
        'pl_chu_truong' => 'Chấp thuận chủ trương đầu tư',
        'pl_moi_truong' => 'ĐTM, cam kết môi trường',
        'pl_nckt' => 'Báo cáo nghiên cứu khả thi',
        'pl_dat_dai' => 'Giao đất, thuê đất',
        'pl_tkkt' => 'Phê duyệt TKKT',
        'pl_pccc' => 'Thẩm duyệt PCCC',
        'pl_tkbvtc' => 'Phê duyệt TKBVTC',
        'pl_xay_dung' => 'Giấy phép xây dựng',
    ];

    /** Tên đầy đủ của các chữ viết tắt trong HO_SO_PHAP_LY (hiện khi rê chuột) */
    public const HO_SO_PHAP_LY_DAY_DU = [
        'pl_moi_truong' => 'Báo cáo đánh giá tác động môi trường, kế hoạch/cam kết bảo vệ môi trường',
        'pl_tkkt' => 'Phê duyệt thiết kế kỹ thuật',
        'pl_pccc' => 'Thẩm duyệt thiết kế phòng cháy chữa cháy',
        'pl_tkbvtc' => 'Phê duyệt thiết kế bản vẽ thi công',
    ];

    protected static function booted(): void
    {
        // Tài khoản chủ đầu tư chỉ được thấy dự án gán cho mình, ở mọi nơi truy vấn
        // model này (danh sách, chi tiết, bản đồ, xuất dữ liệu) — không cần sửa từng nơi.
        static::addGlobalScope('chu_dau_tu', function (Builder $q) {
            $nguoiDung = auth()->user();
            // Trừ khi quản trị đã cấp quyền "Xem tất cả dự án" cho tài khoản đó (trang Tài khoản → Phân quyền)
            if ($nguoiDung && $nguoiDung->vai_tro === 'chu_dau_tu' && ! $nguoiDung->duocXem('tat_ca_du_an')) {
                $q->where('chu_dau_tu_id', $nguoiDung->id);
            }
        });
    }

    public function nguoiCapNhat()
    {
        return $this->belongsTo(User::class, 'nguoi_cap_nhat_id');
    }

    public function taiKhoanChuDauTu()
    {
        return $this->belongsTo(User::class, 'chu_dau_tu_id');
    }

    public function hinhHoc()
    {
        return $this->hasMany(DuAnHinhHoc::class)->orderBy('thu_tu');
    }

    /**
     * Các lớp hình học gom thành nhóm theo (loại, tên, biểu tượng, màu) cho bản đồ trong trang chi tiết
     * dự án và tuỳ chọn "hiện chi tiết" ở bản đồ chung — một dự án điện gió có thể có hàng trăm đoạn
     * "Tuyến cáp điện thu gom", liệt kê từng đoạn là vô dụng, gom lại thì bật/tắt cả nhóm được ngay.
     */
    public function nhomHinhHoc(): array
    {
        return $this->hinhHoc
            ->groupBy(fn ($h) => $h->loai.'|'.trim((string) $h->ten).'|'.$h->bieu_tuong.'|'.$h->mau)
            ->values()
            ->map(fn ($ds) => [
                'loai' => $ds[0]->loai,
                'ten' => trim((string) $ds[0]->ten) ?: DuAnHinhHoc::LOAI[$ds[0]->loai],
                'bieu_tuong' => $ds[0]->bieu_tuong,
                'mau' => $ds[0]->mau,
                // Người nhập đã ẩn cả nhóm (VD điểm cột của đường dây) thì mặc định tắt, vẫn bật lại được
                'hien' => $ds->contains('hien_thi', true),
                'doi_tuong' => $ds->map(fn ($h) => [
                    'toa_do' => $h->toa_do,
                    'nhan' => $h->nhan_diem,
                    'ten' => $h->ten,
                ])->values()->all(),
            ])
            ->sortBy(fn ($n) => ['vung' => 0, 'duong' => 1, 'diem' => 2][$n['loai']] ?? 3)
            ->values()
            ->all();
    }

    /** Văn bản của cả 8 hồ sơ pháp lý (cột ho_so), mỗi văn bản có thể kèm 1 tệp — sửa ở form và ở trang chi tiết */
    public function vanBanPhapLy()
    {
        return $this->hasMany(DuAnVanBan::class)->orderByDesc('ngay_van_ban')->orderBy('thu_tu');
    }

    /** Các văn bản chủ trương đầu tư (chấp thuận lần đầu, các lần điều chỉnh...) — một dự án thường có
     *  nhiều quyết định qua các lần điều chỉnh. Dùng cho số văn bản đại diện, báo cáo tháng. */
    public function vanBan()
    {
        return $this->vanBanPhapLy()->where('ho_so', 'pl_chu_truong');
    }

    /** Tệp hồ sơ pháp lý kiểu cũ (bảng du_an_ho_so_tep, không còn ghi mới — văn bản nằm ở vanBanPhapLy) */
    public function hoSoTep()
    {
        return $this->hasMany(DuAnHoSoTep::class)->orderBy('id');
    }

    /** Văn bản chủ trương đầu tư hiện hành (ngày mới nhất) — dùng cho cột "Chủ trương đầu tư"
     *  ở danh sách, tránh phải tải cả danh sách vanBan() chỉ để lấy 1 dòng đại diện. */
    public function vanBanChinh()
    {
        return $this->hasOne(DuAnVanBan::class)
            ->where('ho_so', 'pl_chu_truong')
            ->orderByRaw('CASE WHEN ngay_van_ban IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('ngay_van_ban')
            ->orderByDesc('id')
            ->limit(1);
    }

    /** Số/ngày văn bản trên chính dự án = văn bản chủ trương mới nhất; hồ sơ nào có văn bản thì tích "đã có" */
    public function dongBoTheoVanBan(array $them = []): void
    {
        $moiNhat = $this->vanBan()->first();
        $duLieu = ['so_van_ban' => $moiNhat?->so_van_ban, 'ngay_van_ban' => $moiNhat?->ngay_van_ban];
        foreach ($this->vanBanPhapLy()->reorder()->distinct()->pluck('ho_so') as $hs) {
            if (array_key_exists($hs, self::HO_SO_PHAP_LY) && ! $this->{$hs}) {
                $duLieu[$hs] = true;
            }
        }
        $this->forceFill(array_merge($duLieu, $them))->save();
    }

    /** Sản lượng điện từng tháng (dự án nguồn điện đã vận hành) */
    public function sanLuongThang()
    {
        return $this->hasMany(SanLuongThang::class);
    }

    /** Các mục báo cáo tuần của dự án này (mỗi báo cáo tuần của chủ đầu tư có 1 mục/dự án) */
    public function baoCaoTuan()
    {
        return $this->hasMany(BaoCaoTuanDuAn::class);
    }

    /** Có lớp đường hoặc vùng (khác điểm đơn lẻ) hay không — dùng để quyết định có cần
     *  hiện thêm chấm điểm đại diện trên bản đồ nữa hay không (đã có hình thật rồi thì thôi). */
    public function getCoHinhDangAttribute(): bool
    {
        return $this->relationLoaded('hinhHoc')
            ? $this->hinhHoc->contains(fn ($h) => in_array($h->loai, ['duong', 'vung'], true) && $h->hien_thi)
            : $this->hinhHoc()->whereIn('loai', ['duong', 'vung'])->where('hien_thi', true)->exists();
    }

    public function getTenLoaiHinhAttribute(): string
    {
        return LoaiHinhDuAn::danhSach()->get($this->loai_hinh)?->ten ?? $this->loai_hinh;
    }

    public function getMauAttribute(): string
    {
        return LoaiHinhDuAn::danhSach()->get($this->loai_hinh)?->mau ?? '#5d6b61';
    }

    /** Icon đại diện của loại hình dự án — chọn ở trang Danh mục, dùng để vẽ điểm trên bản đồ */
    public function getBieuTuongLoaiHinhAttribute(): string
    {
        return LoaiHinhDuAn::danhSach()->get($this->loai_hinh)?->bieu_tuong ?? 'khac';
    }

    public function getSoHoSoDaCoAttribute(): int
    {
        return collect(array_keys(self::HO_SO_PHAP_LY))
            ->filter(fn ($k) => (bool) $this->{$k})
            ->count();
    }

    /** Danh sách địa bàn đã chọn, tương thích với bản ghi cũ chỉ có dia_diem. */
    public function diaDiemHienNayDanhSach(): array
    {
        $diaDiems = $this->dia_diem_hien_nay;
        if (is_array($diaDiems) && $diaDiems !== []) {
            return array_values($diaDiems);
        }

        $diaDiemCu = $this->getRawOriginal('dia_diem');

        return filled($diaDiemCu) ? [$diaDiemCu] : [];
    }

    public function getDiaDiemAttribute(mixed $value): ?string
    {
        $diaDiems = $this->dia_diem_hien_nay;

        return is_array($diaDiems)
            ? ($diaDiems === [] ? null : implode(', ', $diaDiems))
            : $value;
    }

    /** Danh sách địa bàn theo địa giới Quảng Bình và Quảng Trị trước sáp nhập. */
    public function diaDiemTruocDayDanhSach(): array
    {
        $diaDiems = $this->dia_diem_truoc_day;

        return is_array($diaDiems) ? array_values($diaDiems) : [];
    }

    public function scopeLoc(Builder $q, array $dk): Builder
    {
        // Tham số lọc lấy từ URL: chỉ nhận giá trị chữ/số — đường dẫn gõ tay kiểu ?tu_khoa[]=… thì bỏ qua
        $dk = array_map(fn ($v) => is_scalar($v) ? (string) $v : null, $dk);

        return $q
            ->when($dk['tu_khoa'] ?? null, function (Builder $q, $tk) {
                $q->where(function (Builder $q) use ($tk) {
                    $q->where('ten', 'like', "%{$tk}%")
                        ->orWhere('chu_dau_tu', 'like', "%{$tk}%")
                        ->orWhere('dia_diem', 'like', "%{$tk}%")
                        ->orWhere('dia_diem_hien_nay', 'like', "%{$tk}%")
                        ->orWhere('dia_diem_truoc_day', 'like', "%{$tk}%")
                        ->orWhere('cong_suat', 'like', "%{$tk}%");
                });
            })
            ->when($dk['loai_hinh'] ?? null, fn (Builder $q, $v) => $q->where('loai_hinh', $v))
            ->when($dk['trang_thai'] ?? null, fn (Builder $q, $v) => $q->where('trang_thai', $v))
            ->when($dk['dia_diem'] ?? null, fn (Builder $q, $v) => $q->where(function (Builder $q) use ($v) {
                $q->whereJsonContains('dia_diem_hien_nay', $v)
                    ->orWhere('dia_diem', $v);
            }))
            ->when($dk['dia_diem_truoc_day'] ?? null, fn (Builder $q, $v) => $q->whereJsonContains('dia_diem_truoc_day', $v))
            // Khác với trang_thai (chỉ giữ giai đoạn MỚI NHẤT, dự án tiến xa hơn sẽ không còn
            // hiện dưới nhãn "Đã chấp thuận..." nữa) — cờ hồ sơ pháp lý giữ nguyên vĩnh viễn một
            // khi đã có, nên đây mới là cách lọc đúng "dự án đã có chủ trương đầu tư" bất kể đang
            // ở giai đoạn nào.
            ->when(($dk['pl_chu_truong'] ?? '') !== '', fn (Builder $q) => $q->where('pl_chu_truong', $dk['pl_chu_truong'] === '1'))
            // Lọc theo 1 hồ sơ pháp lý bất kỳ: "pl_xxx:1" (đã có) hoặc "pl_xxx:0" (còn thiếu)
            ->when($dk['ho_so'] ?? null, function (Builder $q, $v) {
                [$cot, $co] = array_pad(explode(':', (string) $v, 2), 2, '1');
                if (array_key_exists($cot, self::HO_SO_PHAP_LY)) {
                    $q->where($cot, $co === '1');
                }
            });
    }
}
