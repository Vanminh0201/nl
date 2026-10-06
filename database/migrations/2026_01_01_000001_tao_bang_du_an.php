<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('du_an', function (Blueprint $t) {
            $t->id();
            $t->string('ten');
            $t->string('chu_dau_tu')->nullable();
            $t->string('loai_hinh', 30)->index();
            $t->string('dia_diem')->nullable()->index();
            $t->string('trang_thai', 60)->index();

            $t->decimal('von_dang_ky', 15, 2)->nullable()->comment('tỷ đồng');
            $t->decimal('dien_tich', 12, 2)->nullable()->comment('ha');
            $t->string('cong_suat')->nullable();
            $t->unsignedTinyInteger('tien_do')->nullable();

            $t->date('ngay_khoi_cong')->nullable();
            $t->date('ngay_hoan_thanh')->nullable();
            $t->string('so_van_ban', 100)->nullable();
            $t->date('ngay_van_ban')->nullable();

            $t->boolean('pl_chu_truong')->default(false);
            $t->boolean('pl_dat_dai')->default(false);
            $t->boolean('pl_moi_truong')->default(false);
            $t->boolean('pl_pccc')->default(false);
            $t->boolean('pl_xay_dung')->default(false);

            $t->decimal('vi_do', 10, 7)->nullable();
            $t->decimal('kinh_do', 10, 7)->nullable();
            $t->json('ranh_gioi')->nullable()->comment('mảng [lat,lng] các đỉnh');

            // kết quả tính sẵn, cập nhật bằng lệnh duan:tinh-khoang-cach
            $t->string('tram_gan_nhat')->nullable();
            $t->unsignedSmallInteger('tram_dien_ap')->nullable();
            $t->decimal('khoang_cach_tram', 8, 2)->nullable()->index();

            $t->text('ghi_chu')->nullable();
            $t->foreignId('nguoi_cap_nhat_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();

            $t->index(['vi_do', 'kinh_do']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('du_an');
    }
};
