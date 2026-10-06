<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('du_an_van_ban', function (Blueprint $table) {
            $table->id();
            $table->foreignId('du_an_id')->constrained('du_an')->cascadeOnDelete();
            $table->string('loai')->nullable();
            $table->string('so_van_ban', 100)->nullable();
            $table->date('ngay_van_ban')->nullable();
            $table->string('tep')->nullable();
            $table->unsignedInteger('thu_tu')->default(0);
            $table->timestamps();
        });

        // Chuyen du lieu van ban chu truong dau tu don le hien co (so_van_ban/ngay_van_ban/tep_chu_truong)
        // sang thanh dong dau tien cua danh sach nhieu van ban, khong lam mat du lieu cu.
        $hienCo = DB::table('du_an')
            ->whereNotNull('so_van_ban')
            ->orWhereNotNull('tep_chu_truong')
            ->get(['id', 'so_van_ban', 'ngay_van_ban', 'tep_chu_truong']);

        foreach ($hienCo as $d) {
            DB::table('du_an_van_ban')->insert([
                'du_an_id' => $d->id,
                'loai' => null,
                'so_van_ban' => $d->so_van_ban,
                'ngay_van_ban' => $d->ngay_van_ban,
                'tep' => $d->tep_chu_truong,
                'thu_tu' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('du_an_van_ban');
    }
};
