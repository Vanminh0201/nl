<?php

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\GhiNhatKy;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class GhiNhatKyTest extends TestCase
{
    public function test_array_session_errors_do_not_report_an_exception(): void
    {
        Exceptions::fake();

        app(GhiNhatKy::class)->terminate(
            $this->requestWithSessionErrors(['email' => ['Thông tin đăng nhập không đúng.']]),
            new Response('', 200),
        );

        Exceptions::assertNothingReported();
    }

    public function test_laravel_error_bag_does_not_report_an_exception(): void
    {
        $errors = new ViewErrorBag;
        $errors->put('default', new MessageBag(['email' => 'Thông tin đăng nhập không đúng.']));
        Exceptions::fake();

        app(GhiNhatKy::class)->terminate(
            $this->requestWithSessionErrors($errors),
            new Response('', 200),
        );

        Exceptions::assertNothingReported();
    }

    private function requestWithSessionErrors(array|ViewErrorBag $errors): Request
    {
        Schema::create('nhat_ky', static function (Blueprint $table): void {
            $table->id();
            $table->string('nhom');
            $table->timestamp('created_at');
        });

        $session = app('session')->driver();
        $session->start();
        $session->put('errors', $errors);

        $request = Request::create('/ca-nhan', 'GET');
        $request->setLaravelSession($session);

        $route = new Route('GET', '/ca-nhan', static fn () => new Response);
        $route->name('ca-nhan');
        $request->setRouteResolver(static fn (): Route => $route);

        return $request;
    }
}
