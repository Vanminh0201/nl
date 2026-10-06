<?php

namespace App\Support;

use App\Models\CaiDat;
use Carbon\CarbonImmutable;

/**
 * Kỳ báo cáo tháng: "Tháng M/YYYY" theo tháng dương lịch, giờ Việt Nam. Chủ đầu tư báo cáo tình
 * hình trong tháng, hạn nộp là ngày N của chính tháng đó (quản trị chỉnh ở Cài đặt báo cáo).
 */
final class KyThang
{
    public readonly CarbonImmutable $tuNgay;

    public readonly CarbonImmutable $denNgay;

    private function __construct(public readonly int $nam, public readonly int $thang)
    {
        $this->tuNgay = CarbonImmutable::create($nam, $thang, 1, 0, 0, 0, KyBaoCao::MUI_GIO);
        $this->denNgay = $this->tuNgay->endOfMonth()->startOfDay();
    }

    public static function hienTai(): self
    {
        return self::chuaNgay(CarbonImmutable::now(KyBaoCao::MUI_GIO));
    }

    public static function chuaNgay(CarbonImmutable $ngay): self
    {
        return new self($ngay->year, $ngay->month);
    }

    public static function tuSo(int $nam, int $thang): self
    {
        return new self($nam, $thang);
    }

    /** Đọc mã dạng "2026-09" từ URL/form; sai định dạng thì trả null. */
    public static function tuMa(mixed $ma): ?self
    {
        if (! is_string($ma) || ! $ma || ! preg_match('/^(\d{4})-(\d{1,2})$/', $ma, $m) || (int) $m[2] < 1 || (int) $m[2] > 12 || (int) $m[1] < 2000) {
            return null;
        }

        return new self((int) $m[1], (int) $m[2]);
    }

    public function ma(): string
    {
        return sprintf('%d-%02d', $this->nam, $this->thang);
    }

    public function ten(): string
    {
        return "Tháng {$this->thang}/{$this->nam}";
    }

    public function truoc(): self
    {
        return self::chuaNgay($this->tuNgay->subMonth());
    }

    public function sau(): self
    {
        return self::chuaNgay($this->tuNgay->addMonth());
    }

    public function laTuongLai(): bool
    {
        return $this->tuNgay->greaterThan(self::hienTai()->tuNgay);
    }

    public function laHienTai(): bool
    {
        return $this->ma() === self::hienTai()->ma();
    }

    public function bang(self $khac): bool
    {
        return $this->ma() === $khac->ma();
    }

    public function somHon(self $khac): bool
    {
        return $this->tuNgay->lessThan($khac->tuNgay);
    }

    public static function ngayHan(): int
    {
        return max(1, min(28, (int) CaiDat::lay('bao_cao_thang.han_nop', config('duan.bao_cao_thang.han_nop', 20))));
    }

    /** Hạn nộp: ngày N của chính tháng báo cáo */
    public function hanNop(): CarbonImmutable
    {
        return $this->tuNgay->setDay(self::ngayHan());
    }

    public function quaHan(): bool
    {
        return CarbonImmutable::now(KyBaoCao::MUI_GIO)->greaterThan($this->hanNop()->endOfDay());
    }

    /** Tháng bắt đầu áp dụng báo cáo tháng: quản trị chọn ở Cài đặt báo cáo, chưa chọn thì theo config */
    public static function batDauApDung(): ?self
    {
        return self::tuMa(CaiDat::lay('bao_cao_thang.thang_bat_dau') ?: config('duan.bao_cao_thang.thang_bat_dau'));
    }

    /** Tháng đầu tiên tài khoản phải báo cáo: muộn hơn giữa tháng bắt đầu áp dụng và tháng tạo tài khoản */
    public static function batDauBaoCao(?\DateTimeInterface $taoTaiKhoan): self
    {
        $ky = self::batDauApDung() ?? self::hienTai();
        if ($taoTaiKhoan) {
            $kyTao = self::chuaNgay(CarbonImmutable::instance($taoTaiKhoan)->setTimezone(KyBaoCao::MUI_GIO));
            if ($ky->somHon($kyTao)) {
                $ky = $kyTao;
            }
        }

        return $ky;
    }

    /** $soThang kỳ liên tiếp kết thúc ở kỳ này, xếp từ cũ đến mới. */
    public function ganDay(int $soThang): array
    {
        $ds = [];
        $ky = $this;
        for ($i = 0; $i < $soThang; $i++) {
            array_unshift($ds, $ky);
            $ky = $ky->truoc();
        }

        return $ds;
    }
}
