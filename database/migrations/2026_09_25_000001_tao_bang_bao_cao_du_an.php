<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bao_cao_du_an', function (Blueprint $t) {
            $t->id();
            $t->foreignId('du_an_id')->constrained('du_an')->cascadeOnDelete();
            $t->foreignId('nguoi_bao_cao_id')->constrained('users')->cascadeOnDelete();
            $t->text('noi_dung')->nullable()->comment('Tình hình, khó khăn, kế hoạch tiếp theo — chủ đầu tư tự viết');
            $t->tinyInteger('tien_do')->comment('Tiến độ (%) tại thời điểm nộp báo cáo, đã áp thẳng vào dự án');
            $t->boolean('pl_chu_truong')->default(false);
            $t->boolean('pl_dat_dai')->default(false);
            $t->boolean('pl_moi_truong')->default(false);
            $t->boolean('pl_pccc')->default(false);
            $t->boolean('pl_xay_dung')->default(false);
            $t->timestamps();

            $t->index(['du_an_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bao_cao_du_an');
    }
};
