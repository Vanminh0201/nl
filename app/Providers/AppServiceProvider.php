<?php

namespace App\Providers;

use App\Observers\GhiThayDoiModel;
use App\Support\NhatKyHoatDong;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bộ gom thay đổi cho nhật ký hoạt động: dùng chung trong suốt 1 request
        $this->app->singleton(NhatKyHoatDong::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Nhật ký hoạt động (trang Nhật ký): thêm/sửa/xoá các dữ liệu chính, đăng nhập/đăng xuất
        foreach (array_keys(NhatKyHoatDong::MO_HINH) as $moHinh) {
            $moHinh::observe(GhiThayDoiModel::class);
        }
        Event::listen(Login::class, fn (Login $e) => app(NhatKyHoatDong::class)
            ->ghiDangNhap($e->remember ? 'Đăng nhập (ghi nhớ đăng nhập)' : 'Đăng nhập', $e->user));
        Event::listen(Logout::class, function (Logout $e) {
            if ($e->user) {
                app(NhatKyHoatDong::class)->ghiDangNhap('Đăng xuất', $e->user);
            }
        });
        Event::listen(Failed::class, fn (Failed $e) => app(NhatKyHoatDong::class)
            ->ghiDangNhap('Đăng nhập không thành công', null, ['email' => mb_substr((string) ($e->credentials['email'] ?? ''), 0, 200)]));
    }
}
