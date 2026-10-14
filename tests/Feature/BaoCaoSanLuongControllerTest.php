<?php

namespace Tests\Feature;

use App\Models\DuAn;
use App\Models\LoaiHinhDuAn;
use App\Models\SanLuongThang;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BaoCaoSanLuongControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tháng cần báo cáo: 9/2026
        $this->travelTo(CarbonImmutable::create(2026, 10, 8, 9, 0, 0, 'Asia/Ho_Chi_Minh'));
    }

    protected function tearDown(): void
    {
        LoaiHinhDuAn::lamMoiBoNho();

        parent::tearDown();
    }

    public function test_estimated_generation_is_added_to_statistics_totals(): void
    {
        $this->createTestSchema();
        ['quanTri' => $quanTri, 'duAn' => $duAn] = $this->createTargetProject();
        SanLuongThang::query()->create([
            'du_an_id' => $duAn->id,
            'nam' => 2026,
            'thang' => 9,
            'san_luong' => 213_206_405,
            'trang_thai' => 'da_tiep_nhan',
        ]);

        $response = $this->actingAs($quanTri)->get(route('bao-cao.san-luong.index', [
            'ky' => '2026-09',
            'du_an' => $duAn->id,
        ]));

        $response->assertSee('Hòa đồng bộ')
            ->assertSee('id="bdChiTiet"', false)
            ->assertSee('được tính vào tổng năm và KPI thống kê')
            ->assertSee('~67,49')
            ->assertSee('550,68')
            ->assertSee('"so":[null,null,null,67.494,67.494,67.494,67.494,67.494,213.206,null,null,null],"uoc_tinh":true', false);
        $response->assertViewHas('bang', function ($bang) use ($duAn): bool {
            $hang = $bang->firstWhere('id', $duAn->id);

            return $hang !== null
                && $hang['thang'][9] === 213_206_405.0
                && $hang['uoc_tinh']['thang'][4] === 67_494_050.0
                && $hang['uoc_tinh']['thang'][8] === 67_494_050.0
                && $hang['tong'] === 550_676_655.0;
        })->assertViewHas('kpi.luy_ke', 550_676_655.0)
            ->assertViewHas('theoThang.2026.4', 67_494_050.0)
            ->assertViewHas('theoThang.2026.9', 213_206_405.0)
            ->assertViewHas('uocTinhTheoThang.4', 67_494_050.0)
            ->assertViewHas('uocTinhTheoThang.9', null)
            ->assertViewHas('kpiThang.tong', 213_206_405.0)
            ->assertViewHas('kpiThang.so_truoc', null)
            ->assertViewHas('chiTiet', function (array $chiTiet): bool {
                return $chiTiet['uoc_tinh']['luy_ke'] === 550_676_655.0
                    && array_sum($chiTiet['uoc_tinh']['thang'])
                        + $chiTiet['uoc_tinh']['san_luong_thang_gan_nhat'] === 550_676_655.0
                    && $chiTiet['thang_bo_sung'][4] === 67_494_050.0
                    && ! array_key_exists(9, $chiTiet['thang_bo_sung']);
            });
    }

    public function test_marks_the_latest_supplied_value_when_no_official_month_is_recorded(): void
    {
        $this->createTestSchema();
        ['quanTri' => $quanTri, 'duAn' => $duAn] = $this->createTargetProject();

        $response = $this->actingAs($quanTri)->get(route('bao-cao.san-luong.index', [
            'ky' => '2026-09',
            'du_an' => $duAn->id,
        ]));

        $response->assertSee('213,21†')
            ->assertSee('Theo tài liệu cung cấp')
            ->assertViewHas('kpi.luy_ke', 550_676_655.0)
            ->assertViewHas('theoThang.2026.9', 213_206_405.0)
            ->assertViewHas('chiTiet', fn (array $chiTiet): bool => $chiTiet['uoc_tinh']['thang_gan_nhat_theo_tai_lieu']);
    }

    public function test_manager_page_combines_submission_status_with_yearly_statistics(): void
    {
        $this->createTestSchema();
        ['quanTri' => $quanTri, 'thuyDienA' => $thuyDienA, 'thuyDienB' => $thuyDienB] = $this->createHydroProjects();
        $this->createOutput($thuyDienA, 2025, 9, 4_500_000);
        $this->createOutput($thuyDienA, 2026, 8, 4_000_000);
        $this->createOutput($thuyDienA, 2026, 9, 5_000_000, 'da_gui');
        $this->createOutput($thuyDienB, 2026, 8, 2_000_000);

        $response = $this->actingAs($quanTri)->get(route('bao-cao.san-luong.index'));

        $response->assertOk()
            ->assertSee('Tình hình gửi báo cáo sản lượng tháng 9/2026')
            ->assertSee('Sản lượng từng dự án năm 2026')
            ->assertSee('Tiếp nhận tất cả (1)')
            ->assertDontSee('Thống kê sản lượng')
            ->assertViewHas('demTinhTrang', ['cho' => 1, 'chua_gui' => 1])
            ->assertViewHas('dem', ['phai' => 2, 'da_gui' => 1, 'chua_gan' => 0])
            ->assertViewHas('kpiThang', function (array $kpiThang): bool {
                // Thủy điện B chưa có tháng 9 nên không vào so sánh: chỉ đối chiếu Thủy điện A (5,0 so với 4,0 và 4,5 triệu kWh)
                return $kpiThang['tong'] === 5_000_000.0
                    && $kpiThang['so_du_an'] === 1
                    && round($kpiThang['so_truoc'], 2) === 25.0
                    && round($kpiThang['so_cung_ky'], 2) === 11.11;
            })
            ->assertViewHas('kpi.luy_ke', 11_000_000.0);
    }

    public function test_projects_of_investors_not_required_to_report_are_not_overdue(): void
    {
        $this->travelTo(CarbonImmutable::create(2026, 10, 15, 9, 0, 0, 'Asia/Ho_Chi_Minh'));
        $this->createTestSchema();
        ['quanTri' => $quanTri, 'chuDauTu' => $chuDauTu, 'thuyDienA' => $thuyDienA, 'thuyDienB' => $thuyDienB] = $this->createHydroProjects();
        $chuDauTu->update(['bc_san_luong' => false]);
        $chuDauTuKhac = User::query()->create(['name' => 'Chủ đầu tư khác', 'email' => 'cdt2@example.test', 'password' => 'password', 'vai_tro' => 'chu_dau_tu']);
        $thuyDienC = DuAn::query()->create([
            'ten' => 'Thủy điện C', 'loai_hinh' => 'thuy_dien', 'trang_thai' => 'Đã đi vào hoạt động', 'cong_suat_mw' => 5, 'chu_dau_tu_id' => $chuDauTuKhac->id,
        ]);
        $this->createOutput($thuyDienA, 2026, 9, 5_000_000, 'da_gui');

        $response = $this->actingAs($quanTri)->get(route('bao-cao.san-luong.index'));

        $response->assertOk()
            ->assertSee('Không yêu cầu báo cáo')
            ->assertViewHas('demTinhTrang', ['cho' => 1, 'khong_yeu_cau' => 1, 'chua_gui' => 1])
            ->assertViewHas('dem', ['phai' => 1, 'da_gui' => 0, 'chua_gan' => 0])
            ->assertViewHas('nhom', function ($nhom) use ($thuyDienB, $thuyDienC): bool {
                $tinhTrang = $nhom->flatten(1)->mapWithKeys(fn ($h) => [$h['du_an']->id => $h['tinh_trang']]);

                return $tinhTrang[$thuyDienB->id] === 'khong_yeu_cau' && $tinhTrang[$thuyDienC->id] === 'chua_gui';
            });
    }

    public function test_investor_not_required_to_report_sees_no_overdue_status(): void
    {
        $this->travelTo(CarbonImmutable::create(2026, 10, 15, 9, 0, 0, 'Asia/Ho_Chi_Minh'));
        $this->createTestSchema();
        ['chuDauTu' => $chuDauTu] = $this->createHydroProjects();
        $chuDauTu->update(['bc_san_luong' => false]);

        $this->actingAs($chuDauTu)->get(route('bao-cao.san-luong.index'))
            ->assertOk()
            ->assertSee('Không yêu cầu báo cáo')
            ->assertDontSee('Quá hạn, chưa gửi');
    }

    public function test_type_filter_applies_to_submission_status_and_statistics(): void
    {
        $this->createTestSchema();
        ['quanTri' => $quanTri, 'thuyDienA' => $thuyDienA] = $this->createHydroProjects();
        LoaiHinhDuAn::query()->create(['ma' => 'dien_gio', 'ten' => 'Điện gió', 'mau' => '#2f6b4f', 'thu_tu' => 2]);
        LoaiHinhDuAn::lamMoiBoNho();
        $dienGio = DuAn::query()->create([
            'ten' => 'NMĐG Hướng Linh 1', 'loai_hinh' => 'dien_gio', 'trang_thai' => 'Đã đi vào hoạt động', 'cong_suat_mw' => 30,
        ]);
        $this->createOutput($thuyDienA, 2026, 9, 5_000_000);
        $this->createOutput($dienGio, 2026, 9, 9_000_000);

        $response = $this->actingAs($quanTri)->get(route('bao-cao.san-luong.index', ['loai_hinh' => 'dien_gio']));

        $response->assertOk()
            ->assertViewHas('nhom', fn ($nhom): bool => $nhom->keys()->all() === ['dien_gio'])
            ->assertViewHas('bang', fn ($bang): bool => $bang->pluck('id')->all() === [$dienGio->id])
            ->assertViewHas('kpi.luy_ke', 9_000_000.0);
    }

    public function test_old_statistics_address_redirects_to_the_merged_page(): void
    {
        $this->createTestSchema();
        ['quanTri' => $quanTri, 'thuyDienA' => $thuyDienA] = $this->createHydroProjects();

        $this->actingAs($quanTri)
            ->get(route('bao-cao.san-luong.thong-ke', ['nam' => 2025, 'loai_hinh' => 'thuy_dien', 'du_an' => $thuyDienA->id]))
            ->assertRedirect(route('bao-cao.san-luong.index', ['loai_hinh' => 'thuy_dien', 'du_an' => $thuyDienA->id, 'ky' => '2025-12']));
        $this->actingAs($quanTri)
            ->get(route('bao-cao.san-luong.thong-ke', ['nam' => 2026]))
            ->assertRedirect(route('bao-cao.san-luong.index'));
    }

    public function test_investor_page_shows_own_report_and_statistics(): void
    {
        $this->createTestSchema();
        ['chuDauTu' => $chuDauTu, 'thuyDienA' => $thuyDienA, 'thuyDienB' => $thuyDienB] = $this->createHydroProjects();
        $thuyDienB->update(['chu_dau_tu_id' => null]);
        $this->createOutput($thuyDienA, 2026, 9, 5_000_000);
        $this->createOutput($thuyDienB, 2026, 9, 2_000_000);

        $response = $this->actingAs($chuDauTu)->get(route('bao-cao.san-luong.index'));

        $response->assertOk()
            ->assertSee('Báo cáo sản lượng điện tháng 9/2026')
            ->assertSee('Lịch sử gửi báo cáo 12 tháng gần nhất')
            ->assertSee('Sản lượng từng dự án năm 2026')
            ->assertDontSee('Thủy điện B')
            ->assertViewHas('bang', fn ($bang): bool => $bang->pluck('id')->all() === [$thuyDienA->id])
            ->assertViewHas('kpi.luy_ke', 5_000_000.0);
        $this->actingAs($chuDauTu)
            ->get(route('bao-cao.san-luong.thong-ke', ['nam' => 2026]))
            ->assertRedirect(route('bao-cao.san-luong.index', ['nam' => 2026]));
    }

    /**
     * @return array{quanTri: User, duAn: DuAn}
     */
    private function createTargetProject(): array
    {
        $quanTri = User::query()->create([
            'name' => 'Quản trị',
            'email' => 'admin@example.test',
            'password' => 'password',
            'vai_tro' => 'quan_tri',
        ]);
        $duAn = DuAn::query()->create([
            'ten' => 'NMNĐ Quảng Trạch I',
            'loai_hinh' => 'nhiet_dien',
            'trang_thai' => 'Đang phát điện thử nghiệm',
            'phat_dien_tu' => '2026-04',
            'cong_suat_mw' => 1200,
            'san_luong_dien' => 0,
        ]);
        LoaiHinhDuAn::query()->create([
            'ma' => 'nhiet_dien',
            'ten' => 'Nhiệt điện',
            'mau' => '#1f4b6e',
            'thu_tu' => 1,
        ]);
        LoaiHinhDuAn::lamMoiBoNho();

        return ['quanTri' => $quanTri, 'duAn' => $duAn];
    }

    /**
     * @return array{quanTri: User, chuDauTu: User, thuyDienA: DuAn, thuyDienB: DuAn}
     */
    private function createHydroProjects(): array
    {
        $quanTri = User::query()->create(['name' => 'Quản trị', 'email' => 'admin@example.test', 'password' => 'password', 'vai_tro' => 'quan_tri']);
        $chuDauTu = User::query()->create([
            'name' => 'Chủ đầu tư', 'email' => 'cdt@example.test', 'password' => 'password', 'vai_tro' => 'chu_dau_tu', 'don_vi' => 'Công ty CP Thủy điện Quảng Trị',
        ]);
        LoaiHinhDuAn::query()->create(['ma' => 'thuy_dien', 'ten' => 'Thủy điện', 'mau' => '#1d6f86', 'thu_tu' => 1]);
        LoaiHinhDuAn::lamMoiBoNho();
        $taoDuAn = fn (string $ten, float $congSuat): DuAn => DuAn::query()->create([
            'ten' => $ten, 'loai_hinh' => 'thuy_dien', 'trang_thai' => 'Đã đi vào hoạt động', 'cong_suat_mw' => $congSuat, 'chu_dau_tu_id' => $chuDauTu->id,
        ]);

        return ['quanTri' => $quanTri, 'chuDauTu' => $chuDauTu, 'thuyDienA' => $taoDuAn('Thủy điện A', 20), 'thuyDienB' => $taoDuAn('Thủy điện B', 10)];
    }

    private function createOutput(DuAn $duAn, int $nam, int $thang, float $sanLuong, string $trangThai = 'da_tiep_nhan'): void
    {
        SanLuongThang::query()->create([
            'du_an_id' => $duAn->id, 'nam' => $nam, 'thang' => $thang, 'san_luong' => $sanLuong, 'trang_thai' => $trangThai, 'nguon' => 'chu_dau_tu',
        ]);
    }

    private function createTestSchema(): void
    {
        Schema::create('users', static function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('vai_tro')->nullable();
            $table->string('don_vi')->nullable();
            $table->boolean('bc_tuan')->nullable();
            $table->boolean('bc_thang')->nullable();
            $table->boolean('bc_san_luong')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('du_an', static function (Blueprint $table): void {
            $table->id();
            $table->string('ten');
            $table->string('loai_hinh')->nullable();
            $table->string('trang_thai')->nullable();
            $table->string('phat_dien_tu')->nullable();
            $table->decimal('cong_suat_mw', 12, 2)->nullable();
            $table->decimal('san_luong_dien', 16, 2)->nullable();
            $table->foreignId('chu_dau_tu_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('loai_hinh_du_an', static function (Blueprint $table): void {
            $table->id();
            $table->string('ma')->unique();
            $table->string('ten');
            $table->string('mau')->nullable();
            $table->string('bieu_tuong')->nullable();
            $table->unsignedSmallInteger('thu_tu')->default(0);
            $table->timestamps();
        });

        Schema::create('san_luong_thang', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('du_an_id');
            $table->unsignedSmallInteger('nam');
            $table->unsignedTinyInteger('thang');
            $table->decimal('san_luong', 16, 1)->nullable();
            $table->text('ghi_chu')->nullable();
            $table->string('nguon')->nullable();
            $table->string('trang_thai');
            $table->timestamp('gui_luc')->nullable();
            $table->timestamp('xu_ly_luc')->nullable();
            $table->foreignId('nguoi_cap_nhat_id')->nullable();
            $table->foreignId('nguoi_xu_ly_id')->nullable();
            $table->text('y_kien_xu_ly')->nullable();
            $table->timestamps();
        });

        Schema::create('cai_dat', static function (Blueprint $table): void {
            $table->string('khoa')->primary();
            $table->text('gia_tri')->nullable();
        });

        foreach (['bao_cao_tuan', 'bao_cao_thang'] as $bang) {
            Schema::create($bang, static function (Blueprint $table): void {
                $table->id();
                $table->foreignId('nguoi_bao_cao_id')->nullable();
                $table->string('trang_thai');
            });
        }
    }
}
