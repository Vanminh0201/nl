<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Chặn mục tài khoản chưa được quản trị cấp quyền xem — dùng dạng duoc_xem:ban_do hoặc duoc_xem:danh_sach,ban_do (1 trong các mục) */
class DuocXem
{
    public function handle(Request $request, Closure $next, string ...$muc): Response
    {
        $nguoiDung = $request->user();

        if (! $nguoiDung || ! $nguoiDung->duocXem(...$muc)) {
            abort(403, 'Tài khoản của bạn chưa được cấp quyền xem mục này — liên hệ quản trị hệ thống.');
        }

        return $next($request);
    }
}
