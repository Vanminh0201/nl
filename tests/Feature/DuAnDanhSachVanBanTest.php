<?php

namespace Tests\Feature;

use App\Models\DuAn;
use App\Models\LoaiHinhDuAn;
use App\Models\User;
use App\Support\NhatKyHoatDong;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DuAnDanhSachVanBanTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Test khác dựng bảng du_an ít cột hơn: xoá bộ nhớ danh sách cột của Eloquent để không bỏ sót cột khi tạo dự án
        (fn () => static::$guardableColumns = [])->call(new DuAn);
        $this->createTestSchema();
        LoaiHinhDuAn::query()->create(['ma' => 'dien_gio', 'ten' => 'Điện gió', 'thu_tu' => 1]);
        LoaiHinhDuAn::lamMoiBoNho();
    }

    protected function tearDown(): void
    {
        LoaiHinhDuAn::lamMoiBoNho();
        $this->app->make(NhatKyHoatDong::class)->layThayDoi();

        parent::tearDown();
    }

    public function test_legal_documents_column_shows_a_view_button_with_documents_grouped_by_dossier(): void
    {
        $coVanBan = DuAn::query()->create(['ten' => 'Điện gió có hồ sơ', 'loai_hinh' => 'dien_gio', 'trang_thai' => 'Đang thi công']);
        DuAn::query()->create(['ten' => 'Điện gió chưa có hồ sơ', 'loai_hinh' => 'dien_gio', 'trang_thai' => 'Đang thi công']);
        $coTep = $coVanBan->vanBanPhapLy()->create([
            'ho_so' => 'pl_moi_truong', 'so_van_ban' => '02/QĐ-UBND', 'ngay_van_ban' => '2026-03-05', 'tep' => 'van-ban/1/dtm.pdf', 'ten_tep' => 'dtm.pdf',
        ]);
        $coVanBan->vanBanPhapLy()->create(['ho_so' => 'pl_chu_truong', 'so_van_ban' => '198/NQ-HĐND', 'loai' => 'Điều chỉnh chủ trương đầu tư']);
        DB::table('du_an_ho_so_tep')->insert([
            'du_an_id' => $coVanBan->id, 'loai' => 'pl_chu_truong', 'ten_tep' => 'chu-truong.pdf', 'duong_dan' => 'x/chu-truong.pdf', 'so_van_ban' => '43/NQ-HĐND',
        ]);

        $this->actingAs($this->createAdmin())->get(route('du-an.index'))
            ->assertOk()
            ->assertSee('data-xem-van-ban="'.$coVanBan->id.'"', false)
            ->assertSee('Xem<span>3</span>', false)
            ->assertSee('Chưa có văn bản')
            // Nhóm theo thứ tự 8 hồ sơ (chủ trương trước môi trường); văn bản có tệp có nút mở tệp
            ->assertSeeInOrder([
                '198/NQ-HĐND', 'Điều chỉnh chủ trương đầu tư', 'Chưa có tệp', '43/NQ-HĐND', 'Mở tệp',
                DuAn::HO_SO_PHAP_LY['pl_moi_truong'], '02/QĐ-UBND', '05/03/2026', 'Mở tệp',
            ])
            ->assertSee(route('van-ban.tep', $coTep), false)
            ->assertDontSee('du-an-van-ban-list', false);
    }

    private function createAdmin(): User
    {
        return User::query()->create([
            'name' => 'Quản trị', 'email' => 'qt@example.test', 'password' => 'password', 'vai_tro' => 'quan_tri', 'da_duyet' => true,
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
            $table->string('chu_dau_tu')->nullable();
            $table->foreignId('chu_dau_tu_id')->nullable();
            $table->string('loai_hinh')->nullable();
            $table->string('dia_diem')->nullable();
            $table->json('dia_diem_hien_nay')->nullable();
            $table->json('dia_diem_truoc_day')->nullable();
            $table->string('trang_thai')->nullable();
            $table->string('cong_suat')->nullable();
            $table->decimal('cong_suat_mw', 12, 2)->nullable();
            $table->decimal('von_dang_ky', 16, 2)->nullable();
            $table->decimal('dien_tich', 14, 2)->nullable();
            $table->decimal('tien_do', 5, 2)->nullable();
            $table->date('ngay_hoan_thanh')->nullable();
            $table->string('tien_do_van_hanh')->nullable();
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
        Schema::create('loai_hinh_du_an', static function (Blueprint $table): void {
            $table->id();
            $table->string('ma')->unique();
            $table->string('ten');
            $table->string('mau')->nullable();
            $table->string('bieu_tuong')->nullable();
            $table->unsignedSmallInteger('thu_tu')->default(0);
            $table->timestamps();
        });
        Schema::create('trang_thai_du_an', static function (Blueprint $table): void {
            $table->id();
            $table->string('ten', 150)->unique();
            $table->string('nhom', 20)->default('binh_thuong');
            $table->unsignedSmallInteger('thu_tu')->default(0);
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
        Schema::create('bao_cao_thang', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('nguoi_bao_cao_id');
            $table->smallInteger('nam');
            $table->tinyInteger('thang');
            $table->string('trang_thai', 20)->default('nhap');
            $table->timestamp('nop_luc')->nullable();
            $table->timestamps();
        });
        Schema::create('cai_dat', static function (Blueprint $table): void {
            $table->string('khoa')->primary();
            $table->text('gia_tri')->nullable();
        });
        Schema::create('san_luong_thang', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('du_an_id');
            $table->unsignedSmallInteger('nam');
            $table->unsignedTinyInteger('thang');
            $table->decimal('san_luong', 16, 1)->nullable();
            $table->string('trang_thai');
            $table->timestamps();
        });
    }
}
