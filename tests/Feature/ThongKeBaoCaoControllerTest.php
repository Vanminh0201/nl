<?php

namespace Tests\Feature;

use App\Models\BaoCaoTuan;
use App\Models\BaoCaoTuanDuAn;
use App\Models\DuAn;
use App\Models\User;
use App\Support\KyBaoCao;
use App\Support\KyThang;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ThongKeBaoCaoControllerTest extends TestCase
{
    public function test_weekly_charts_use_the_latest_submitted_values_for_each_project(): void
    {
        $this->createTestSchema();
        $this->travelTo('2026-10-08 10:00:00');
        $quanTri = User::factory()->create(['vai_tro' => 'quan_tri']);
        $nguoiBaoCaoCu = User::factory()->create(['vai_tro' => 'chu_dau_tu']);
        $nguoiBaoCaoMoi = User::factory()->create(['vai_tro' => 'chu_dau_tu']);
        $duAnA = DuAn::query()->create(['ten' => 'Dự án A', 'loai_hinh' => 'dien_gio', 'trang_thai' => 'Đang triển khai']);
        $duAnB = DuAn::query()->create(['ten' => 'Dự án B', 'loai_hinh' => 'dien_gio', 'trang_thai' => 'Đang triển khai']);
        $duAnC = DuAn::query()->create(['ten' => 'Dự án C', 'loai_hinh' => 'dien_gio', 'trang_thai' => 'Đang triển khai']);
        $duAnChuaBaoCao = DuAn::query()->create(['ten' => 'Dự án chưa báo cáo', 'loai_hinh' => 'dien_gio', 'trang_thai' => 'Đang triển khai']);
        $ky = KyBaoCao::tuSo(2026, 40);
        $kyTruoc = $ky->truoc();

        $baoCaoTuanTruoc = BaoCaoTuan::query()->create([
            'nguoi_bao_cao_id' => $nguoiBaoCaoCu->id,
            'nam' => $kyTruoc->nam,
            'tuan' => $kyTruoc->tuan,
            'tu_ngay' => $kyTruoc->tuNgay->toDateString(),
            'den_ngay' => $kyTruoc->denNgay->toDateString(),
            'trang_thai' => 'da_tiep_nhan',
            'nop_luc' => now()->subDays(8),
        ]);
        $baoCaoCu = BaoCaoTuan::query()->create([
            'nguoi_bao_cao_id' => $nguoiBaoCaoCu->id,
            'nam' => $ky->nam,
            'tuan' => $ky->tuan,
            'tu_ngay' => $ky->tuNgay->toDateString(),
            'den_ngay' => $ky->denNgay->toDateString(),
            'trang_thai' => 'da_nop',
            'nop_luc' => now()->subDay(),
        ]);
        $baoCaoMoi = BaoCaoTuan::query()->create([
            'nguoi_bao_cao_id' => $nguoiBaoCaoMoi->id,
            'nam' => $ky->nam,
            'tuan' => $ky->tuan,
            'tu_ngay' => $ky->tuNgay->toDateString(),
            'den_ngay' => $ky->denNgay->toDateString(),
            'trang_thai' => 'da_nop',
            'nop_luc' => now(),
        ]);

        foreach ([
            [$baoCaoTuanTruoc, $duAnA, 50, 15, 100],
            [$baoCaoTuanTruoc, $duAnB, 40, 25, 200],
            [$baoCaoCu, $duAnA, 20, 5, 100],
            [$baoCaoCu, $duAnB, 40, 10, 200],
            [$baoCaoMoi, $duAnA, 70, 30, 100],
            [$baoCaoMoi, $duAnB, 50, 25, 200],
            [$baoCaoMoi, $duAnC, 10, 80, 100],
        ] as [$baoCao, $duAn, $tienDo, $giaiNgan, $keHoach]) {
            BaoCaoTuanDuAn::query()->create([
                'bao_cao_tuan_id' => $baoCao->id,
                'du_an_id' => $duAn->id,
                'tien_do' => $tienDo,
                'giai_ngan_nam' => $giaiNgan,
                'ke_hoach_von_nam' => $keHoach,
            ]);
        }

        $response = $this->actingAs($quanTri)->get(route('bao-cao.thong-ke'));

        $response->assertSee('Thống kê báo cáo')
            ->assertSee('Tiến độ &amp; giải ngân', false)
            ->assertDontSee('bd-tien-do-tuan', false)
            ->assertDontSee('aria-label="Nội dung thống kê"', false)
            ->assertSee(route('bao-cao.tien-do'), false)
            ->assertViewHas('tabTienDo', false)
            ->assertViewHas('bieuDoTienDo', function (array $diem) use ($ky, $kyTruoc): bool {
                $cacDiem = collect($diem)->keyBy('nhan');
                $tuan = $cacDiem->get($ky->ten());
                $tuanTruoc = $cacDiem->get($kyTruoc->ten());

                return $tuan !== null
                    && $tuanTruoc !== null
                    && $tuan['tien_do'] === 43.33
                    && $tuan['so_du_an_tien_do'] === 3
                    && $tuan['tong_du_an_trong_pham_vi'] === 4
                    && $tuan['tien_do_chenh_lech'] === 15.0
                    && $tuan['so_du_an_so_sanh_tien_do'] === 2
                    && $tuan['giai_ngan_nam'] === 135.0
                    && $tuan['giai_ngan_chenh_lech'] === 15.0
                    && $tuan['giai_ngan_thay_doi_phan_tram'] === 37.5
                    && $tuan['so_du_an_so_sanh_giai_ngan'] === 2
                    && $tuan['ke_hoach_von_nam'] === 400.0
                    && $tuanTruoc['tien_do'] === 45.0
                    && $tuanTruoc['giai_ngan_nam'] === 40.0;
            })
            ->assertViewHas('tienDoTheoDuAn', function (array $cacDuAn) use ($duAnChuaBaoCao): bool {
                $duAnChuaCoSoLieu = collect($cacDuAn)->firstWhere('id', $duAnChuaBaoCao->id);

                return count($cacDuAn) === 4
                    && $duAnChuaCoSoLieu !== null
                    && $duAnChuaCoSoLieu['ky'] === null
                    && $duAnChuaCoSoLieu['tien_do'] === null;
            });

        $this->actingAs($quanTri)
            ->get(route('bao-cao.tien-do', [
                'du_an' => $duAnA->id,
                'thang' => KyThang::chuaNgay($ky->denNgay)->ma(),
            ]))
            ->assertSee('bd-tien-do-tuan', false)
            ->assertSee('bd-giai-ngan-tuan', false)
            ->assertSee('data-tk-chart="tien-do"', false)
            ->assertSee('data-tk-chart="giai-ngan"', false)
            ->assertSee('TỔNG QUAN DỰ ÁN')
            ->assertSee('Tiến độ và giải ngân theo từng dự án')
            ->assertSee('data-tk-du-lieu', false)
            ->assertSee('aria-label="Gõ để tìm và chọn dự án"', false)
            ->assertSee('data-tk-project-picker', false)
            ->assertSee('data-project-name="Dự án A"', false)
            ->assertSee('js/bao-cao-tien-do.js', false)
            ->assertDontSee('cdnjs.cloudflare.com/ajax/libs/chart.js', false)
            ->assertSee('So sánh biến động theo tuần')
            ->assertDontSee('aria-label="Nội dung thống kê"', false)
            ->assertSee(route('bao-cao.thong-ke'), false)
            ->assertSee('Tăng / giảm (tỷ đồng)')
            ->assertViewHas('bieuDoTienDo', function (array $diem) use ($ky): bool {
                $cacDiem = collect($diem)->keyBy('nhan');
                $tuan = $cacDiem->get($ky->ten());

                return $tuan !== null
                    && $tuan['tien_do'] === 70.0
                    && $tuan['so_du_an_tien_do'] === 1
                    && $tuan['giai_ngan_nam'] === 30.0
                    && $tuan['tien_do_chenh_lech'] === 20.0
                    && $tuan['giai_ngan_chenh_lech'] === 15.0
                    && $tuan['giai_ngan_thay_doi_phan_tram'] === 100.0
                    && $tuan['ke_hoach_von_nam'] === 100.0;
            })
            ->assertViewHas('tienDoTheoDuAn', function (array $cacDuAn) use ($duAnA): bool {
                return count($cacDuAn) === 1
                    && $cacDuAn[0]['id'] === $duAnA->id
                    && $cacDuAn[0]['tien_do'] === 70.0
                    && $cacDuAn[0]['tien_do_chenh_lech'] === 20.0
                    && $cacDuAn[0]['giai_ngan_nam'] === 30.0
                    && $cacDuAn[0]['giai_ngan_chenh_lech'] === 15.0;
            })
            ->assertViewHas('tongDuAnTrongPhamVi', 1);

        $this->actingAs($quanTri)
            ->get(route('bao-cao.thong-ke', ['tab' => 'tien-do']))
            ->assertSee('bd-tien-do-tuan', false)
            ->assertViewHas('tabTienDo', true);
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
            $table->boolean('da_duyet')->default(true);
            $table->json('du_an_cho_duyet')->nullable();
            $table->json('quyen_xem')->nullable();
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
            $table->foreignId('chu_dau_tu_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('bao_cao_tuan', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('nguoi_bao_cao_id');
            $table->smallInteger('nam');
            $table->tinyInteger('tuan');
            $table->date('tu_ngay');
            $table->date('den_ngay');
            $table->string('trang_thai');
            $table->timestamp('nop_luc')->nullable();
            $table->timestamps();
        });

        Schema::create('bao_cao_tuan_du_an', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bao_cao_tuan_id');
            $table->foreignId('du_an_id');
            $table->unsignedTinyInteger('tien_do')->nullable();
            $table->decimal('giai_ngan_nam', 15, 3)->nullable();
            $table->decimal('ke_hoach_von_nam', 15, 3)->nullable();
            $table->timestamps();
        });

        Schema::create('bao_cao_thang', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('nguoi_bao_cao_id');
            $table->smallInteger('nam');
            $table->tinyInteger('thang');
            $table->string('trang_thai');
            $table->timestamp('nop_luc')->nullable();
            $table->timestamps();
        });

        Schema::create('cai_dat', static function (Blueprint $table): void {
            $table->string('khoa')->primary();
            $table->text('gia_tri')->nullable();
        });

        Schema::create('san_luong_thang', static function (Blueprint $table): void {
            $table->id();
            $table->string('trang_thai');
        });
    }
}
