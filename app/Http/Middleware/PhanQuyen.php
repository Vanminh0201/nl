<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PhanQuyen
{
    public function handle(Request $request, Closure $next, string ...$vaiTro): Response
    {
        $nguoiDung = $request->user();

        if (! $nguoiDung || ! in_array($nguoiDung->vai_tro, $vaiTro, true)) {
            abort(403, 'Tài khoản của bạn không có quyền thực hiện thao tác này.');
        }

        return $next($request);
    }
}
