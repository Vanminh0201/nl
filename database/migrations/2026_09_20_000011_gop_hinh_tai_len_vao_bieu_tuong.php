<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bieu_tuong', function (Blueprint $t) {
            $t->string('tep')->nullable()->after('hinh')
                ->comment('File SVG/PNG tự tải lên (public/bieu-tuong-tai-len/), ưu tiên hơn hinh nếu có');
        });

        // hinh_tai_len chỉ là bảng trung gian tạm thời, chưa có dữ liệu thật nào tham chiếu tới —
        // gộp thẳng khả năng tải file vào bieu_tuong cho gọn, không cần 2 danh mục tách rời.
        Schema::dropIfExists('hinh_tai_len');
    }

    public function down(): void
    {
        Schema::create('hinh_tai_len', function (Blueprint $t) {
            $t->id();
            $t->string('ten', 100);
            $t->string('tep');
            $t->unsignedSmallInteger('thu_tu')->default(0);
            $t->timestamps();
        });

        Schema::table('bieu_tuong', function (Blueprint $t) {
            $t->dropColumn('tep');
        });
    }
};
