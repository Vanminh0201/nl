<?php

namespace Tests\Feature\Models;

use App\Models\DuAn;
use App\Support\DiaDiemQuangTriTruocSapNhap;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DuAnTest extends TestCase
{
    public function test_project_exposes_documents_from_the_current_and_legacy_legal_document_tables(): void
    {
        $this->createDocumentSchema();

        $duAnId = DB::table('du_an')->insertGetId([
            'ten' => 'Dự án có đầy đủ hồ sơ',
        ]);
        DB::table('du_an_van_ban')->insert([
            [
                'du_an_id' => $duAnId,
                'ho_so' => 'pl_chu_truong',
                'so_van_ban' => '01/QĐ',
                'thu_tu' => 0,
            ],
            [
                'du_an_id' => $duAnId,
                'ho_so' => 'pl_moi_truong',
                'so_van_ban' => '02/QĐ',
                'thu_tu' => 1,
            ],
        ]);
        DB::table('du_an_ho_so_tep')->insert([
            'du_an_id' => $duAnId,
            'loai' => 'pl_dat_dai',
            'ten_tep' => 'giao-dat.pdf',
            'duong_dan' => 'van-ban/giao-dat.pdf',
        ]);

        $duAn = DuAn::query()
            ->with(['vanBanPhapLy', 'hoSoTep'])
            ->findOrFail($duAnId);

        $this->assertSame(2, $duAn->vanBanPhapLy->count());
        $this->assertSame('Chấp thuận chủ trương đầu tư', $duAn->vanBanPhapLy[0]->ten_ho_so);
        $this->assertSame(1, $duAn->hoSoTep->count());
    }

    public function test_policy_document_selection_includes_undated_documents_and_prefers_the_latest_known_date(): void
    {
        $this->createDocumentSchema();

        $duAnChiCoVanBanKhongNgay = DB::table('du_an')->insertGetId([
            'ten' => 'Dự án có văn bản chưa nhập ngày',
        ]);
        $vanBanKhongNgay = DB::table('du_an_van_ban')->insertGetId([
            'du_an_id' => $duAnChiCoVanBanKhongNgay,
            'ho_so' => 'pl_chu_truong',
            'tep' => '4213.pdf',
        ]);

        $duAnCoNhieuVanBan = DB::table('du_an')->insertGetId([
            'ten' => 'Dự án có nhiều văn bản',
        ]);
        DB::table('du_an_van_ban')->insert([
            [
                'du_an_id' => $duAnCoNhieuVanBan,
                'ho_so' => 'pl_chu_truong',
                'so_van_ban' => '01/QĐ',
                'ngay_van_ban' => '2025-01-01',
                'tep' => null,
            ],
            [
                'du_an_id' => $duAnCoNhieuVanBan,
                'ho_so' => 'pl_chu_truong',
                'so_van_ban' => '02/QĐ',
                'ngay_van_ban' => '2026-01-01',
                'tep' => null,
            ],
            [
                'du_an_id' => $duAnCoNhieuVanBan,
                'ho_so' => 'pl_chu_truong',
                'so_van_ban' => null,
                'ngay_van_ban' => null,
                'tep' => 'van-ban-chua-ngay.pdf',
            ],
        ]);

        $duAns = DuAn::query()
            ->with('vanBanChinh')
            ->whereIn('id', [$duAnChiCoVanBanKhongNgay, $duAnCoNhieuVanBan])
            ->get()
            ->keyBy('id');

        $this->assertSame($vanBanKhongNgay, $duAns[$duAnChiCoVanBanKhongNgay]->vanBanChinh?->id);
        $this->assertSame('02/QĐ', $duAns[$duAnCoNhieuVanBan]->vanBanChinh?->so_van_ban);
    }

    public function test_project_location_filter_matches_any_selected_area_and_reads_legacy_location(): void
    {
        Schema::create('du_an', static function (Blueprint $table): void {
            $table->id();
            $table->string('ten');
            $table->string('dia_diem')->nullable();
            $table->json('dia_diem_hien_nay')->nullable();
            $table->json('dia_diem_truoc_day')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        $duAnNhieuDiaDiemId = DB::table('du_an')->insertGetId([
            'ten' => 'Dự án liên xã',
            'dia_diem' => 'Xã Cam Lộ',
            'dia_diem_hien_nay' => json_encode(['Xã Cam Lộ', 'Xã Gio Linh']),
            'dia_diem_truoc_day' => json_encode(['Xã Triệu Nguyên — Huyện Đa Krông, tỉnh Quảng Trị']),
        ]);
        $duAnCuId = DB::table('du_an')->insertGetId([
            'ten' => 'Dự án dữ liệu cũ',
            'dia_diem' => 'Xã Triệu Phong cũ',
        ]);

        $ketQuaLoc = DuAn::query()->loc(['dia_diem' => 'Xã Gio Linh'])->pluck('id')->all();
        $ketQuaLocCu = DuAn::query()
            ->loc(['dia_diem_truoc_day' => 'Xã Triệu Nguyên — Huyện Đa Krông, tỉnh Quảng Trị'])
            ->pluck('id')
            ->all();
        $duAnNhieuDiaDiem = DuAn::findOrFail($duAnNhieuDiaDiemId);
        $duAnCu = DuAn::findOrFail($duAnCuId);

        $this->assertSame([$duAnNhieuDiaDiemId], $ketQuaLoc);
        $this->assertSame(['Xã Cam Lộ', 'Xã Gio Linh'], $duAnNhieuDiaDiem->diaDiemHienNayDanhSach());
        $this->assertSame('Xã Cam Lộ, Xã Gio Linh', $duAnNhieuDiaDiem->dia_diem);
        $this->assertSame(['Xã Triệu Nguyên — Huyện Đa Krông, tỉnh Quảng Trị'], $duAnNhieuDiaDiem->diaDiemTruocDayDanhSach());
        $this->assertSame([$duAnNhieuDiaDiemId], $ketQuaLocCu);
        $this->assertSame(['Xã Triệu Phong cũ'], $duAnCu->diaDiemHienNayDanhSach());
    }

    public function test_location_migration_backfills_current_historical_and_unmatched_legacy_values(): void
    {
        Schema::create('du_an', static function (Blueprint $table): void {
            $table->id();
            $table->string('ten');
            $table->string('dia_diem')->nullable();
            $table->timestamps();
        });

        $hienNayId = DB::table('du_an')->insertGetId([
            'ten' => 'Địa điểm hiện nay',
            'dia_diem' => 'phường đông hà',
        ]);
        $truocDayId = DB::table('du_an')->insertGetId([
            'ten' => 'Địa điểm trước đây',
            'dia_diem' => 'Xã Triệu Nguyên',
        ]);
        $khongDoiChieuId = DB::table('du_an')->insertGetId([
            'ten' => 'Địa điểm cũ chưa đối chiếu',
            'dia_diem' => 'Địa điểm lưu tự do',
        ]);

        $migration = require database_path('migrations/2026_10_07_100522_add_project_location_columns_to_du_an_table.php');
        $migration->up();

        $this->assertDatabaseHas('du_an', [
            'id' => $hienNayId,
            'dia_diem' => 'Phường Đông Hà',
            'dia_diem_hien_nay' => json_encode(['Phường Đông Hà'], JSON_UNESCAPED_UNICODE),
            'dia_diem_truoc_day' => '[]',
        ]);
        $this->assertDatabaseHas('du_an', [
            'id' => $truocDayId,
            'dia_diem' => null,
            'dia_diem_hien_nay' => '[]',
            'dia_diem_truoc_day' => json_encode([
                DiaDiemQuangTriTruocSapNhap::chuanHoa('Xã Triệu Nguyên'),
            ], JSON_UNESCAPED_UNICODE),
        ]);
        $this->assertDatabaseHas('du_an', [
            'id' => $khongDoiChieuId,
            'dia_diem' => null,
            'dia_diem_hien_nay' => '[]',
            'dia_diem_truoc_day' => json_encode(['Địa điểm lưu tự do'], JSON_UNESCAPED_UNICODE),
        ]);
    }

    private function createDocumentSchema(): void
    {
        Schema::create('du_an', static function (Blueprint $table): void {
            $table->id();
            $table->string('ten');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('du_an_van_ban', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('du_an_id');
            $table->string('ho_so');
            $table->string('so_van_ban')->nullable();
            $table->date('ngay_van_ban')->nullable();
            $table->string('loai')->nullable();
            $table->string('tep')->nullable();
            $table->string('ten_tep')->nullable();
            $table->unsignedBigInteger('kich_thuoc')->nullable();
            $table->unsignedInteger('thu_tu')->default(0);
            $table->timestamps();
        });

        Schema::create('du_an_ho_so_tep', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('du_an_id');
            $table->string('loai');
            $table->string('ten_tep');
            $table->string('duong_dan');
            $table->unsignedBigInteger('kich_thuoc')->default(0);
            $table->string('so_van_ban')->nullable();
            $table->date('ngay_van_ban')->nullable();
            $table->unsignedBigInteger('nguoi_tai_id')->nullable();
            $table->timestamps();
        });
    }
}
