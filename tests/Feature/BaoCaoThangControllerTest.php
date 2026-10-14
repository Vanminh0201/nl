<?php

namespace Tests\Feature;

use App\Models\BaoCaoThang;
use App\Models\BaoCaoThangDuAn;
use App\Models\BaoCaoThangTongHop;
use App\Models\DuAn;
use App\Models\LoaiHinhDuAn;
use App\Models\User;
use App\Services\TongHopBaoCaoThang;
use App\Support\KyThang;
use App\Support\NhatKyHoatDong;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use ZipArchive;

class BaoCaoThangControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-09 09:00:00');
        // Eloquent nhớ danh sách cột từng bảng (để xét $guarded) suốt tiến trình test; test khác dựng bảng du_an ít cột hơn
        // thì cột dia_diem_hien_nay, chu_dau_tu… bị bỏ qua khi tạo dự án ở đây — xoá bộ nhớ đó
        (fn () => static::$guardableColumns = [])->call(new DuAn);
        $this->createTestSchema();
        foreach ([['thuy_dien', 'Thủy điện', 1], ['dien_gio', 'Điện gió', 2], ['dien_mat_troi', 'Điện mặt trời', 3], ['kcn', 'Khu công nghiệp', 9]] as [$ma, $ten, $tt]) {
            LoaiHinhDuAn::query()->create(['ma' => $ma, 'ten' => $ten, 'thu_tu' => $tt]);
        }
        LoaiHinhDuAn::lamMoiBoNho();
    }

    protected function tearDown(): void
    {
        LoaiHinhDuAn::lamMoiBoNho();
        // Thay đổi model tạo thẳng trong test (không qua request) được gom để ghi nhật ký lúc tiến trình kết thúc — khi đó ứng
        // dụng đã bị huỷ; xả bộ đệm để không còn gì phải ghi
        $this->app->make(NhatKyHoatDong::class)->layThayDoi();

        parent::tearDown();
    }

    public function test_summary_groups_energy_projects_by_commune_then_status(): void
    {
        $this->createProject('NMĐG Hướng Linh 1', 'dien_gio', ['Xã Hướng Phùng'], 'Đã đi vào hoạt động', 30);
        $this->createProject('Điện gió Win 1', 'dien_gio', ['Xã Hướng Phùng', 'Xã Khe Sanh'], 'Đang thi công', 48);
        $this->createProject('Thủy điện Bản Mới', 'thuy_dien', ['Xã Khe Sanh'], 'Đang nghiên cứu, đề xuất', 29.4);
        $this->createProject('Điện mặt trời Ái Tử', 'dien_mat_troi', ['Xã Ái Tử'], 'Đã chấp thuận chủ trương đầu tư', 40);
        $this->createProject('Điện gió Cam Lộ', 'dien_gio', ['Xã Cam Lộ'], 'Đang nghiên cứu, đề xuất', 20);
        $this->createProject('Điện gió Đakrông', 'dien_gio', ['Xã Đakrông'], 'Đang nghiên cứu, đề xuất', 20);
        $this->createProject('Điện gió chưa có xã', 'dien_gio', [], 'Đang nghiên cứu, đề xuất', 10);
        $this->createProject('KCN Quảng Trị', 'kcn', ['Xã Ái Tử'], 'Đang thi công', null);

        $bang = app(TongHopBaoCaoThang::class)->cuaKy(KyThang::tuSo(2026, 10));

        $this->assertSame([
            'I. XÃ HƯỚNG PHÙNG (2 dự án)', 'II. XÃ KHE SANH (2 dự án)', 'III. XÃ ÁI TỬ (1 dự án)', 'IV. XÃ CAM LỘ (1 dự án)',
            'V. XÃ ĐAKRÔNG (1 dự án)', TongHopBaoCaoThang::NHOM_CHUA_RO_XA.' (1 dự án)',
        ], array_column($bang['xa'], 'ten'));
        $khoa = fn (array $xa) => array_map(fn ($n) => [$n['ten'], array_map(fn ($m) => $m['tt'].'. '.$m['ten'], $n['muc'])], $xa['nhom']);
        $this->assertSame([
            ['Đã hoàn thành, vận hành thương mại', ['1. NMĐG Hướng Linh 1']],
            ['Đang triển khai', ['2. Điện gió Win 1']],
        ], $khoa($bang['xa'][0]));
        $this->assertSame([
            ['Đang triển khai', ['1. Điện gió Win 1']],
            ['Chưa triển khai', ['2. Thủy điện Bản Mới']],
        ], $khoa($bang['xa'][1]));
        // Đã chấp thuận chủ trương đầu tư tính là đang triển khai
        $this->assertSame([['Đang triển khai', ['1. Điện mặt trời Ái Tử']]], $khoa($bang['xa'][2]));
        $this->assertSame('Công suất: 29,4 MW', $bang['xa'][1]['nhom'][1]['muc'][0]['cong_suat']);

        $this->assertSame(['mot_xa' => 5, 'nhieu_xa' => 1, 'chua_ro' => 1], $bang['thong_ke']['tong']);
        $this->assertSame(['Điện gió', 'Thủy điện', 'Điện mặt trời'], array_column($bang['thong_ke']['hang'], 'loai_hinh'));
        $this->assertSame([['ten' => 'ĐIỆN GIÓ - 1 DỰ ÁN', 'muc' => [['stt' => 1, 'ten' => 'Điện gió Win 1', 'xa' => 'Hướng Phùng, Khe Sanh']]]], $bang['nhieu_xa']);
    }

    public function test_multi_commune_list_follows_the_order_of_the_commune_table(): void
    {
        $this->createProject('Thủy điện Khe Sanh', 'thuy_dien', ['Xã Khe Sanh'], 'Đang nghiên cứu, đề xuất', 10);
        $this->createProject('Điện gió B', 'dien_gio', ['Xã Khe Sanh', 'Xã Hướng Phùng'], 'Đang nghiên cứu, đề xuất', 10);
        $this->createProject('Điện gió C', 'dien_gio', ['Xã Hướng Phùng'], 'Đang nghiên cứu, đề xuất', 10);
        $this->createProject('Điện gió D', 'dien_gio', ['Xã Hướng Phùng'], 'Đang nghiên cứu, đề xuất', 10);
        $this->createProject('Điện gió A Lưới', 'dien_gio', ['Xã Ái Tử', 'Xã Khe Sanh'], 'Đang nghiên cứu, đề xuất', 10);
        $this->createProject('Thủy điện Hướng Phùng - Khe Sanh', 'thuy_dien', ['Xã Khe Sanh', 'Xã Hướng Phùng'], 'Đang nghiên cứu, đề xuất', 10);

        $bang = app(TongHopBaoCaoThang::class)->cuaKy(KyThang::tuSo(2026, 10));

        $this->assertSame(['I. XÃ HƯỚNG PHÙNG (4 dự án)', 'II. XÃ KHE SANH (4 dự án)', 'III. XÃ ÁI TỬ (1 dự án)'], array_column($bang['xa'], 'ten'));
        // Loại hình nhiều dự án trước; dự án theo thứ tự xuất hiện lần đầu trong bảng (không theo tên), các xã theo thứ tự xã
        // trong bảng; STT liên tục qua các loại hình
        $this->assertSame([
            ['ten' => 'ĐIỆN GIÓ - 2 DỰ ÁN', 'muc' => [
                ['stt' => 1, 'ten' => 'Điện gió B', 'xa' => 'Hướng Phùng, Khe Sanh'],
                ['stt' => 2, 'ten' => 'Điện gió A Lưới', 'xa' => 'Khe Sanh, Ái Tử'],
            ]],
            ['ten' => 'THỦY ĐIỆN - 1 DỰ ÁN', 'muc' => [['stt' => 3, 'ten' => 'Thủy điện Hướng Phùng - Khe Sanh', 'xa' => 'Hướng Phùng, Khe Sanh']]],
        ], $bang['nhieu_xa']);
        $this->assertSame(['Điện gió', 'Thủy điện'], array_column($bang['thong_ke']['hang'], 'loai_hinh'));
    }

    public function test_summary_content_prefers_department_edits_then_this_months_report_then_latest_month(): void
    {
        ['duAn' => $duAn, 'chuDauTu' => $chuDauTu] = $this->createReportedProject();
        $this->createReport($chuDauTu, $duAn, 2026, 8, 'da_tiep_nhan', 'Tình hình tháng 8');
        $thang9 = $this->createReport($chuDauTu, $duAn, 2026, 9, 'da_nop', 'Tình hình tháng 9');
        $tongHop = app(TongHopBaoCaoThang::class);

        $noiDung = $tongHop->noiDung(KyThang::tuSo(2026, 9))[$duAn->id];
        $this->assertSame(['chu_dau_tu', 'Tình hình tháng 9'], [$noiDung['nguon'], $noiDung['noi_dung']['tinh_hinh']]);

        BaoCaoThangTongHop::query()->create(['nam' => 2026, 'thang' => 9, 'du_an_id' => $duAn->id, 'tinh_hinh' => 'Sở biên tập tháng 9']);
        $noiDung = $tongHop->noiDung(KyThang::tuSo(2026, 9))[$duAn->id];
        $this->assertSame(['so', 'Sở biên tập tháng 9'], [$noiDung['nguon'], $noiDung['noi_dung']['tinh_hinh']]);

        // Tháng 10 chưa có gì: chép nội dung gần nhất (Sở biên tập tháng 9)
        $noiDung = $tongHop->noiDung(KyThang::tuSo(2026, 10))[$duAn->id];
        $this->assertSame(['so', '2026-09'], [$noiDung['nguon'], $noiDung['ky']->ma()]);

        // Báo cáo đang bị trả bổ sung không lấy: tháng 9 quay về báo cáo tháng 8
        BaoCaoThangTongHop::query()->delete();
        $thang9->update(['trang_thai' => 'yeu_cau_bo_sung']);
        $noiDung = $tongHop->noiDung(KyThang::tuSo(2026, 9))[$duAn->id];
        $this->assertSame(['Tình hình tháng 8', '2026-08'], [$noiDung['noi_dung']['tinh_hinh'], $noiDung['ky']->ma()]);
    }

    public function test_rows_of_reports_sent_with_the_old_template_are_mapped_to_the_new_columns(): void
    {
        $dong = new BaoCaoThangDuAn([
            'tinh_hinh' => 'Đang thi công móng trụ', 'phuong_an' => 'Đề nghị UBND tỉnh chỉ đạo GPMB', 'cod_nam' => 2027, 'cod_ky' => 'Q2',
        ]);

        $this->assertSame([
            'du_kien_khoi_cong' => null,
            'du_kien_hoan_thanh' => 'Dự kiến COD: Quý II/2027',
            'tinh_hinh' => 'Đang thi công móng trụ',
            'kho_khan' => null,
            'kien_nghi_xa' => null,
            'kien_nghi_so' => 'Đề nghị UBND tỉnh chỉ đạo GPMB',
        ], $dong->noiDungMau());
    }

    public function test_investor_submits_report_with_the_template_columns(): void
    {
        ['duAn' => $duAn, 'chuDauTu' => $chuDauTu] = $this->createReportedProject();

        $this->actingAs($chuDauTu)->get(route('bao-cao.thang.soan'))
            ->assertOk()
            ->assertSee('Dự kiến khởi công')
            ->assertSee('Khó khăn vướng mắc')
            ->assertSee('Kiến nghị xử lý — Sở, ngành')
            ->assertDontSee('Tình trạng pháp lý dự án');

        $this->actingAs($chuDauTu)->post(route('bao-cao.thang.luu'), [
            'ky' => '2026-10', 'hanh_dong' => 'nop', 'ten_don_vi' => 'Công ty CP Điện gió Win',
            'du_an' => [$duAn->id => ['co' => 1, 'tinh_hinh' => '', 'kho_khan' => 'GPMB chậm']],
        ])->assertSessionHasErrors("du_an.{$duAn->id}.tinh_hinh");

        $this->actingAs($chuDauTu)->post(route('bao-cao.thang.luu'), [
            'ky' => '2026-10', 'hanh_dong' => 'nop', 'ten_don_vi' => 'Công ty CP Điện gió Win',
            'du_an' => [$duAn->id => [
                'co' => 1, 'du_kien_khoi_cong' => 'Tháng 12/2026', 'du_kien_hoan_thanh' => 'Tháng 6/2027', 'tinh_hinh' => 'Đang thi công',
                'kho_khan' => 'GPMB chậm', 'kien_nghi_xa' => 'Xã hỗ trợ GPMB', 'kien_nghi_so' => 'Sở NN&MT hướng dẫn',
            ]],
        ])->assertRedirect();

        $this->assertSame([
            'du_kien_khoi_cong' => 'Tháng 12/2026', 'du_kien_hoan_thanh' => 'Tháng 6/2027', 'tinh_hinh' => 'Đang thi công',
            'kho_khan' => 'GPMB chậm', 'kien_nghi_xa' => 'Xã hỗ trợ GPMB', 'kien_nghi_so' => 'Sở NN&MT hướng dẫn',
        ], BaoCaoThangDuAn::query()->sole()->noiDungMau());
    }

    public function test_department_edits_a_project_in_the_summary_and_blanking_all_fields_reverts(): void
    {
        ['duAn' => $duAn, 'chuDauTu' => $chuDauTu] = $this->createReportedProject();
        $quanTri = $this->createUser('quan_tri');

        $this->actingAs($chuDauTu)->post(route('bao-cao.thang.tong-hop-luu', $duAn), ['ky' => '2026-10', 'tinh_hinh' => 'x'])->assertForbidden();

        $this->actingAs($quanTri)->post(route('bao-cao.thang.tong-hop-luu', $duAn), [
            'ky' => '2026-10', 'tinh_hinh' => '  Sở tổng hợp  ', 'kien_nghi_so' => 'Sở Tài chính đẩy nhanh thẩm định',
        ])->assertRedirect(route('bao-cao.thang.index', ['ky' => '2026-10']).'#th-'.$duAn->id);
        $this->assertSame(['Sở tổng hợp', 'Sở Tài chính đẩy nhanh thẩm định', $quanTri->id],
            [BaoCaoThangTongHop::query()->sole()->tinh_hinh, BaoCaoThangTongHop::query()->sole()->kien_nghi_so, BaoCaoThangTongHop::query()->sole()->nguoi_sua_id]);

        $this->actingAs($quanTri)->post(route('bao-cao.thang.tong-hop-luu', $duAn), ['ky' => '2026-10', 'tinh_hinh' => ' '])->assertRedirect();
        $this->assertSame(0, BaoCaoThangTongHop::query()->count());
    }

    public function test_manager_page_print_view_and_downloads_follow_the_commune_template(): void
    {
        ['duAn' => $duAn, 'chuDauTu' => $chuDauTu] = $this->createReportedProject();
        $this->createReport($chuDauTu, $duAn, 2026, 10, 'da_nop', 'Đã lắp 10/30 ống trụ tháp');
        $quanTri = $this->createUser('quan_tri');

        $this->actingAs($quanTri)->get(route('bao-cao.thang.index', ['ky' => '2026-10']))
            ->assertOk()
            ->assertSee('Tình hình triển khai các dự án năng lượng')
            ->assertSee('I. XÃ HƯỚNG PHÙNG (1 dự án)')
            ->assertSee('Đã lắp 10/30 ống trụ tháp')
            ->assertSee('Chủ đầu tư · T10/2026')
            ->assertSeeInOrder(['Tổng số dự án', 'Trong 1 xã', 'Từ 2 xã trở lên', 'Cơ cấu'])
            ->assertSee('Danh sách dự án trên địa bàn từ 2 xã trở lên');
        $this->actingAs($quanTri)->get(route('bao-cao.thang.tong-hop-in', ['ky' => '2026-10']))
            ->assertOk()->assertSee('THỐNG KÊ CÁC DỰ ÁN NĂNG LƯỢNG TRÊN ĐỊA BÀN CÁC XÃ');
        $this->actingAs($quanTri)->get(route('bao-cao.thang.tong-hop-excel', ['ky' => '2026-10']))->assertOk();

        $word = $this->actingAs($quanTri)->get(route('bao-cao.thang.tong-hop-word', ['ky' => '2026-10']));
        $word->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $zip = new ZipArchive;
        $zip->open($word->baseResponse->getFile()->getPathname());
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        $this->assertStringContainsString('<w:gridSpan w:val="2"/>', $xml);
        $this->assertStringContainsString('<w:vMerge w:val="restart"/>', $xml);
        $this->assertStringContainsString('I. XÃ HƯỚNG PHÙNG (1 dự án)', $xml);
        $this->assertStringContainsString('Đã lắp 10/30 ống trụ tháp', $xml);
    }

    private function createProject(string $ten, string $loaiHinh, array $xa, string $trangThai, ?float $congSuat, ?int $chuDauTuId = null): DuAn
    {
        return DuAn::query()->create([
            'ten' => $ten, 'loai_hinh' => $loaiHinh, 'dia_diem_hien_nay' => $xa, 'trang_thai' => $trangThai,
            'cong_suat_mw' => $congSuat, 'chu_dau_tu' => 'Công ty '.$ten, 'chu_dau_tu_id' => $chuDauTuId,
        ]);
    }

    private function createUser(string $vaiTro): User
    {
        static $so = 0;
        $so++;

        return User::query()->create([
            'name' => "Người dùng $so", 'email' => "nd$so@example.test", 'password' => 'password', 'vai_tro' => $vaiTro,
            'da_duyet' => true, 'bc_thang' => true, 'created_at' => '2026-01-01 00:00:00',
        ]);
    }

    /** @return array{duAn: DuAn, chuDauTu: User} */
    private function createReportedProject(): array
    {
        $chuDauTu = $this->createUser('chu_dau_tu');

        return ['duAn' => $this->createProject('Điện gió Hướng Phùng 1', 'dien_gio', ['Xã Hướng Phùng'], 'Đang thi công', 30, $chuDauTu->id), 'chuDauTu' => $chuDauTu];
    }

    private function createReport(User $chuDauTu, DuAn $duAn, int $nam, int $thang, string $trangThai, string $tinhHinh): BaoCaoThang
    {
        $baoCao = BaoCaoThang::query()->create([
            'nguoi_bao_cao_id' => $chuDauTu->id, 'nam' => $nam, 'thang' => $thang, 'trang_thai' => $trangThai, 'nop_luc' => now(),
        ]);
        $baoCao->chiTiet()->create(['du_an_id' => $duAn->id, 'tinh_hinh' => $tinhHinh]);

        return $baoCao;
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
            $table->date('ngay_khoi_cong')->nullable();
            $table->date('ngay_hoan_thanh')->nullable();
            $table->string('tien_do_van_hanh')->nullable();
            $table->string('phat_dien_tu')->nullable();
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
        Schema::create('bao_cao_thang', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('nguoi_bao_cao_id');
            $table->smallInteger('nam');
            $table->tinyInteger('thang');
            $table->string('ten_don_vi')->nullable();
            $table->string('nguoi_lap')->nullable();
            $table->string('dien_thoai', 50)->nullable();
            $table->string('trang_thai', 20)->default('nhap');
            $table->timestamp('nop_luc')->nullable();
            $table->foreignId('nguoi_xu_ly_id')->nullable();
            $table->timestamp('xu_ly_luc')->nullable();
            $table->text('y_kien_xu_ly')->nullable();
            $table->timestamps();
        });
        Schema::create('bao_cao_thang_du_an', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bao_cao_thang_id');
            $table->foreignId('du_an_id');
            foreach (['dia_diem', 'chu_dau_tu', 'du_kien_khoi_cong', 'du_kien_hoan_thanh', 'phap_ly', 'tien_do_cod', 'tien_do_thuc_te',
                'tinh_hinh', 'kho_khan', 'kien_nghi_xa', 'kien_nghi_so', 'phuong_an', 'ghi_chu'] as $cot) {
                $table->text($cot)->nullable();
            }
            $table->string('cong_suat')->nullable();
            $table->smallInteger('cod_nam')->nullable();
            $table->string('cod_ky', 10)->nullable();
            $table->string('nhom', 30)->nullable();
            $table->timestamps();
        });
        Schema::create('bao_cao_thang_tong_hop', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('nam');
            $table->unsignedTinyInteger('thang');
            $table->foreignId('du_an_id');
            foreach (['du_kien_khoi_cong', 'du_kien_hoan_thanh', 'tinh_hinh', 'kho_khan', 'kien_nghi_xa', 'kien_nghi_so'] as $cot) {
                $table->text($cot)->nullable();
            }
            $table->foreignId('nguoi_sua_id')->nullable();
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
            $table->text('cong_viec_dang_lam')->nullable();
            $table->text('kho_khan')->nullable();
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
