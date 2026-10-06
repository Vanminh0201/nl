<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('du_an_hinh_hoc', function (Blueprint $t) {
            $t->id();
            $t->foreignId('du_an_id')->constrained('du_an')->cascadeOnDelete();
            $t->string('loai', 10)->comment('diem | duong | vung');
            $t->string('ten')->nullable()->comment('tên lấy từ Placemark khi nhập file, có thể để trống');
            $t->json('toa_do')->comment('mảng [[lat,lng], ...]; diem luôn có đúng 1 phần tử');
            $t->boolean('hien_thi')->default(true);
            $t->unsignedSmallInteger('thu_tu')->default(0);
            $t->timestamps();
        });

        // Chuyển dữ liệu ranh_gioi/ranh_gioi_dang cũ (mỗi dự án tối đa 1 đường hoặc 1 vùng)
        // sang bảng mới trước khi bỏ 2 cột này, không để mất dữ liệu đã nhập.
        DB::table('du_an')->whereNotNull('ranh_gioi')->orderBy('id')
            ->each(function ($d) {
                $toaDo = json_decode($d->ranh_gioi, true);
                if (! is_array($toaDo) || ! count($toaDo)) {
                    return;
                }
                DB::table('du_an_hinh_hoc')->insert([
                    'du_an_id' => $d->id,
                    'loai' => $d->ranh_gioi_dang === 'duong' ? 'duong' : 'vung',
                    'ten' => null,
                    'toa_do' => $d->ranh_gioi,
                    'hien_thi' => true,
                    'thu_tu' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::table('du_an', function (Blueprint $t) {
            $t->dropColumn(['ranh_gioi', 'ranh_gioi_dang']);
        });
    }

    public function down(): void
    {
        Schema::table('du_an', function (Blueprint $t) {
            $t->json('ranh_gioi')->nullable()->comment('mảng [lat,lng] các đỉnh');
            $t->string('ranh_gioi_dang', 10)->nullable();
        });

        DB::table('du_an_hinh_hoc')->whereIn('loai', ['duong', 'vung'])->orderBy('du_an_id')
            ->each(function ($h) {
                DB::table('du_an')->where('id', $h->du_an_id)->update([
                    'ranh_gioi' => $h->toa_do,
                    'ranh_gioi_dang' => $h->loai,
                ]);
            });

        Schema::dropIfExists('du_an_hinh_hoc');
    }
};
