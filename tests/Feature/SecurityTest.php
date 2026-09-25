<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Support\TrackingUrl;
use Database\Seeders\HelpdeskSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Tests\TestCase;

/** Header keamanan, CSP tanpa inline, halaman error, dan penanganan CSRF kedaluwarsa. */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_and_csp_are_sent(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $csp = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringNotContainsString('unsafe-inline', $csp);
    }

    /** CSP melarang script/style inline: setiap halaman tidak boleh memakainya. */
    public function test_pages_contain_no_inline_script_or_style(): void
    {
        $this->seed(HelpdeskSeeder::class);
        $ticket = Ticket::factory()->create();
        $pages = ['/', '/antrian', '/lacak', '/admin/login', '/admin/lupa-password', TrackingUrl::make($ticket), '/tidak-ada'];

        foreach ($pages as $url) {
            $this->assertNoInlineCode($this->get($url)->getContent(), $url);
        }

        $this->actingAs(User::factory()->admin()->create());
        foreach (['/admin', '/admin/tiket', '/admin/tiket/'.$ticket->id, '/admin/layanan', '/admin/user', '/admin/log/aktivitas', '/admin/log/email', '/admin/profil/password'] as $url) {
            $this->assertNoInlineCode($this->get($url)->assertOk()->getContent(), $url);
        }
    }

    public function test_error_pages_use_friendly_layout(): void
    {
        $this->get('/tidak-ada')->assertNotFound()->assertSee('Halaman tidak ditemukan')->assertSee('Kembali ke beranda');

        $this->actingAs(User::factory()->create())->get('/admin/user')->assertForbidden()->assertSee('Akses ditolak');
    }

    public function test_expired_csrf_returns_to_form_with_old_input(): void
    {
        $this->withMiddleware();
        // Paksa token tidak cocok walau di environment testing.
        $this->app->instance(ValidateCsrfToken::class, new class($this->app, $this->app['encrypter']) extends ValidateCsrfToken
        {
            public function handle($request, \Closure $next)
            {
                throw new TokenMismatchException;
            }
        });

        $this->from('/')->post('/tiket', ['requester_name' => 'Budi', 'description' => 'Laptop mati', '_token' => 'basi'])
            ->assertRedirect('/')
            ->assertSessionHas('error', 'Halaman terlalu lama dibuka. Isian kamu masih ada, silakan kirim ulang.')
            ->assertSessionHasInput('requester_name', 'Budi');
    }

    private function assertNoInlineCode(string|false $html, string $url): void
    {
        $html = (string) $html;
        $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*\bsrc=)[^>]*>/i', $html, "Script inline di {$url}");
        $this->assertDoesNotMatchRegularExpression('/<style[\s>]/i', $html, "Style inline di {$url}");
        $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=/i', $html, "Atribut style di {$url}");
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=/i', $html, "Event handler inline di {$url}");
    }
}
