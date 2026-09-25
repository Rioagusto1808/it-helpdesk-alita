<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Header keamanan untuk semua response web. CSP: hanya aset sendiri + Google Fonts, tanpa script/style inline. */
final class SecurityHeaders
{
    private const CSP = [
        "default-src 'self'",
        "script-src 'self'",
        "style-src 'self' https://fonts.googleapis.com",
        "font-src 'self' https://fonts.gstatic.com",
        "img-src 'self' data:",
        "connect-src 'self'",
        "object-src 'none'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-ancestors 'none'",
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // Halaman error debug Laravel memakai style inline; di production APP_DEBUG=false sehingga CSP selalu aktif.
        if (! (config('app.debug') && $response->getStatusCode() >= 500)) {
            $response->headers->set('Content-Security-Policy', implode('; ', self::CSP));
        }

        return $response;
    }
}
