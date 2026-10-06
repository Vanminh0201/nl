<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bieu_tuong', function (Blueprint $t) {
            $t->id();
            $t->string('ma', 30)->unique()->comment('Khoá ổn định, lưu trong loai_hinh_du_an.bieu_tuong và du_an_hinh_hoc.bieu_tuong');
            $t->string('ten', 100);
            $t->string('hinh', 30)->comment('Hình vẽ sẵn trong public/js/bieu-tuong.js (không tự nhập SVG để tránh rủi ro)');
            $t->unsignedSmallInteger('thu_tu')->default(0);
            $t->timestamps();
        });

        // Giữ nguyên 15 biểu tượng đang dùng, ma = hinh (vẽ đúng hình đã có, không đổi giao diện hiện tại).
        $ds = [
            ['diem_moc', 'Điểm mốc (mặc định)'],
            ['tram_dien', 'Trạm biến áp / cột điện'],
            ['toa_nha', 'Toà nhà, văn phòng'],
            ['kho', 'Nhà kho, kho bãi'],
            ['canh_bao', 'Cảnh báo, lưu ý'],
            ['ccn', 'Cụm công nghiệp'],
            ['kcn', 'Khu công nghiệp'],
            ['che_bien', 'Chế biến nông lâm thuỷ sản'],
            ['vlxd', 'Vật liệu xây dựng'],
            ['khoang_san', 'Khai thác khoáng sản'],
            ['thuy_dien', 'Thuỷ điện'],
            ['dien_gio', 'Điện gió'],
            ['dien_mat_troi', 'Điện mặt trời'],
            ['det_may', 'Dệt may, da giày'],
            ['khac', 'Khác'],
        ];

        foreach ($ds as $i => [$ma, $ten]) {
            DB::table('bieu_tuong')->insert([
                'ma' => $ma,
                'ten' => $ten,
                'hinh' => $ma,
                'thu_tu' => $i + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bieu_tuong');
    }
};
