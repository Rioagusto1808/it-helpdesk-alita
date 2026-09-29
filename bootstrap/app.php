<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
        $middleware->web(append: [SecurityHeaders::class]);
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'active' => EnsureUserIsActive::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Link tracking kedaluwarsa atau diubah: halaman 403 ramah dengan jalan keluar ke /lacak.
        $exceptions->render(fn (InvalidSignatureException $e) => response()->view('tracking.invalid-link', status: 403));

        // Form terlalu lama dibuka (CSRF kedaluwarsa): kembali ke form dengan isian lama, bukan halaman 419.
        // Laravel sudah mengubah TokenMismatchException menjadi HttpException 419 sebelum callback ini dipanggil.
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419 || $request->expectsJson() || ! $request->hasSession()) {
                return null;
            }

            return back()
                ->withInput($request->except(['_token', 'password', 'password_confirmation', 'current_password', 'attachment']))
                ->with('error', 'Halaman terlalu lama dibuka. Isian kamu masih ada, silakan kirim ulang.');
        });
    })->create();
