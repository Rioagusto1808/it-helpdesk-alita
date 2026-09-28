<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Production di belakang WAF: header X-Forwarded-* hanya dipercaya dari IP di TRUSTED_PROXIES. */
class TrustedProxyTest extends TestCase
{
    use RefreshDatabase;

    private const FORWARDED = [
        'X-Forwarded-Proto' => 'https',
        'X-Forwarded-Host' => 'ticketing.alita.id',
        'X-Forwarded-Port' => '443',
        'X-Forwarded-For' => '36.70.1.2',
    ];

    public function test_trusted_proxy_headers_give_https_urls_and_real_client_ip(): void
    {
        config(['trustedproxy.proxies' => '10.0.5.1']);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.5.1'])
            ->withHeaders(self::FORWARDED)
            ->get('/');

        $response->assertOk()->assertSee('https://ticketing.alita.id/css/base.css', false);
        $this->assertSame('36.70.1.2', request()->ip());
    }

    public function test_headers_from_untrusted_address_are_ignored(): void
    {
        config(['trustedproxy.proxies' => '10.0.5.1']);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->withHeaders(self::FORWARDED)
            ->get('/');

        $response->assertOk()->assertDontSee('https://ticketing.alita.id', false);
        $this->assertSame('203.0.113.9', request()->ip());
    }
}
