<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Session user yang sudah dinonaktifkan langsung diputus, tidak menunggu session habis. */
final class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_active === false) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return to_route('admin.login')->withErrors(['email' => 'Akun kamu sudah dinonaktifkan. Hubungi admin.']);
        }

        return $next($request);
    }
}
