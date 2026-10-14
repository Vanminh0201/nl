<?php

namespace Tests\Feature;

use App\Models\DuAn;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RegistrationProjectApprovalTest extends TestCase
{
    public function test_registration_only_stores_a_pending_project_request(): void
    {
        $this->createTestSchema();
        $duAn = $this->createProject('Điện gió mẫu');
        Model::unsetEventDispatcher();

        $response = $this->post('/dang-ky', [
            'name' => 'Nhà đầu tư thử nghiệm',
            'email' => 'investor@example.com',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'du_an_ids' => [$duAn->id],
        ]);

        $taiKhoan = User::where('email', 'investor@example.com')->firstOrFail();
        $response->assertRedirect(route('dang-nhap'));
        $this->assertFalse($taiKhoan->da_duyet);
        $this->assertSame([$duAn->id], $taiKhoan->du_an_cho_duyet);
        $this->assertNull($duAn->fresh()->chu_dau_tu_id);
    }

    public function test_admin_approval_assigns_requested_projects_and_approves_atomically(): void
    {
        $this->createTestSchema();
        $quanTri = $this->createUser('admin@example.com', 'quan_tri', true);
        $taiKhoan = $this->createUser('investor@example.com', 'chu_dau_tu', false, [1]);
        $duAnDuocYeuCau = $this->createProject('Điện gió được yêu cầu');
        $duAnDuocChon = $this->createProject('Thủy điện được admin chọn');
        Model::unsetEventDispatcher();

        $response = $this->actingAs($quanTri)->patch('/tai-khoan/'.$taiKhoan->id.'/duyet', [
            'du_an_ids' => [$duAnDuocChon->id],
        ]);

        $response->assertRedirect();
        $this->assertTrue($taiKhoan->fresh()->da_duyet);
        $this->assertNull($taiKhoan->fresh()->du_an_cho_duyet);
        $this->assertSame($taiKhoan->id, $duAnDuocChon->fresh()->chu_dau_tu_id);
        $this->assertNull($duAnDuocYeuCau->fresh()->chu_dau_tu_id);
    }

    public function test_admin_approval_keeps_account_pending_if_a_requested_project_was_claimed(): void
    {
        $this->createTestSchema();
        $quanTri = $this->createUser('admin@example.com', 'quan_tri', true);
        $taiKhoan = $this->createUser('investor@example.com', 'chu_dau_tu', false, [1]);
        $duAn = $this->createProject('Điện gió mẫu');
        DB::table('du_an')->where('id', $duAn->id)->update(['chu_dau_tu_id' => $quanTri->id]);
        Model::unsetEventDispatcher();

        $response = $this->actingAs($quanTri)->patch('/tai-khoan/'.$taiKhoan->id.'/duyet', [
            'du_an_ids' => [$duAn->id],
        ]);

        $response->assertSessionHasErrors('du_an_ids');
        $this->assertFalse($taiKhoan->fresh()->da_duyet);
        $this->assertSame([1], $taiKhoan->fresh()->du_an_cho_duyet);
        $this->assertSame($quanTri->id, $duAn->fresh()->chu_dau_tu_id);
    }

    public function test_admin_can_approve_an_account_without_assigning_any_project(): void
    {
        $this->createTestSchema();
        $quanTri = $this->createUser('admin@example.com', 'quan_tri', true);
        $taiKhoan = $this->createUser('investor@example.com', 'chu_dau_tu', false, [1]);
        $duAn = $this->createProject('Điện gió mẫu');
        Model::unsetEventDispatcher();

        $response = $this->actingAs($quanTri)->patch('/tai-khoan/'.$taiKhoan->id.'/duyet');

        $response->assertRedirect();
        $this->assertTrue($taiKhoan->fresh()->da_duyet);
        $this->assertNull($taiKhoan->fresh()->du_an_cho_duyet);
        $this->assertNull($duAn->fresh()->chu_dau_tu_id);
    }

    public function test_non_admin_cannot_approve_an_account_or_assign_projects(): void
    {
        $this->createTestSchema();
        $chuDauTu = $this->createUser('owner@example.com', 'chu_dau_tu', true);
        $taiKhoanChoDuyet = $this->createUser('pending@example.com', 'chu_dau_tu', false);
        $duAn = $this->createProject('Điện gió mẫu');
        Model::unsetEventDispatcher();

        $response = $this->actingAs($chuDauTu)->patch('/tai-khoan/'.$taiKhoanChoDuyet->id.'/duyet', [
            'du_an_ids' => [$duAn->id],
        ]);

        $response->assertForbidden();
        $this->assertFalse($taiKhoanChoDuyet->fresh()->da_duyet);
        $this->assertNull($duAn->fresh()->chu_dau_tu_id);
    }

    public function test_admin_approval_accepts_a_project_already_assigned_to_that_account(): void
    {
        $this->createTestSchema();
        $quanTri = $this->createUser('admin@example.com', 'quan_tri', true);
        $taiKhoan = $this->createUser('investor@example.com', 'chu_dau_tu', false, [1]);
        $duAn = $this->createProject('Điện gió mẫu');
        DB::table('du_an')->where('id', $duAn->id)->update(['chu_dau_tu_id' => $taiKhoan->id]);
        Model::unsetEventDispatcher();

        $response = $this->actingAs($quanTri)->patch('/tai-khoan/'.$taiKhoan->id.'/duyet', [
            'du_an_ids' => [$duAn->id],
        ]);

        $response->assertRedirect();
        $this->assertTrue($taiKhoan->fresh()->da_duyet);
        $this->assertSame($taiKhoan->id, $duAn->fresh()->chu_dau_tu_id);
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
            $table->string('dia_diem')->nullable();
            $table->foreignId('chu_dau_tu_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('nhat_ky', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('ten_nguoi_dung')->nullable();
            $table->string('vai_tro', 30)->nullable();
            $table->string('nhom', 20);
            $table->string('hanh_dong');
            $table->string('doi_tuong', 500)->nullable();
            $table->string('lien_ket', 500)->nullable();
            $table->json('chi_tiet')->nullable();
            $table->string('phuong_thuc', 10)->nullable();
            $table->string('duong_dan', 500)->nullable();
            $table->unsignedSmallInteger('ma_phan_hoi')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('trinh_duyet')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    private function createProject(string $name): DuAn
    {
        $id = DB::table('du_an')->insertGetId([
            'ten' => $name,
            'loai_hinh' => 'dien_gio',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DuAn::query()->findOrFail($id);
    }

    /**
     * @param  array<int>  $duAnChoDuyet
     */
    private function createUser(string $email, string $role, bool $approved, array $duAnChoDuyet = []): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => $email,
            'email' => $email,
            'password' => Hash::make('secure-password'),
            'vai_tro' => $role,
            'da_duyet' => $approved,
            'du_an_cho_duyet' => $duAnChoDuyet ? json_encode($duAnChoDuyet) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::query()->findOrFail($id);
    }
}
