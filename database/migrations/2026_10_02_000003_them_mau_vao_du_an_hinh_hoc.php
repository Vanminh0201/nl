<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Màu riêng cho lớp đường/vùng của dự án (VD màu quy ước từng loại đất trong bản đồ quy hoạch khu
// công nghiệp); null = bản đồ chi tiết tự chọn màu như trước
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('du_an_hinh_hoc', function (Blueprint $t) {
            $t->string('mau', 7)->nullable()->after('bieu_tuong');
        });
    }

    public function down(): void
    {
        Schema::table('du_an_hinh_hoc', function (Blueprint $t) {
            $t->dropColumn('mau');
        });
    }
};
