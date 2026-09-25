<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'password1234';

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->get('/admin/profil/password')->assertRedirect(route('admin.login'));
        $this->get('/admin/login')->assertOk()->assertSee('Masuk ke panel admin');
    }

    public function test_user_can_login_and_is_recorded(): void
    {
        $user = User::factory()->create(['email' => 'rio@alita.id']);

        $this->post('/admin/login', ['email' => 'RIO@alita.id', 'password' => self::PASSWORD])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()?->last_login_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'auth.login', 'actor_id' => $user->id]);
        $this->get('/admin')->assertOk()->assertSee($user->name);
    }

    public function test_wrong_password_fails_with_generic_message_and_is_logged(): void
    {
        User::factory()->create(['email' => 'rio@alita.id']);

        $this->from('/admin/login')
            ->post('/admin/login', ['email' => 'rio@alita.id', 'password' => 'salah-sekali'])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors(['email' => 'Email atau password salah.']);

        $this->assertGuest();
        $this->assertDatabaseHas('activity_logs', ['action' => 'auth.login_failed', 'actor_id' => null]);
        $this->assertSame('rio@alita.id', ActivityLog::query()->where('action', 'auth.login_failed')->sole()->properties['email'] ?? null);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->inactive()->create(['email' => 'lama@alita.id']);

        $this->post('/admin/login', ['email' => 'lama@alita.id', 'password' => self::PASSWORD])
            ->assertSessionHasErrors(['email' => 'Email atau password salah.']);

        $this->assertGuest();
    }

    public function test_active_session_of_deactivated_user_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/admin')->assertOk();

        $user->update(['is_active' => false]);

        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }

    public function test_login_is_throttled_after_five_attempts(): void
    {
        User::factory()->create(['email' => 'rio@alita.id']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', ['email' => 'rio@alita.id', 'password' => 'salah-'.$i]);
        }

        $this->post('/admin/login', ['email' => 'rio@alita.id', 'password' => self::PASSWORD])->assertTooManyRequests();
        $this->assertGuest();
    }

    public function test_logout_ends_session_and_is_logged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/admin/logout')->assertRedirect(route('admin.login'));

        $this->assertGuest();
        $this->assertDatabaseHas('activity_logs', ['action' => 'auth.logout', 'actor_id' => $user->id]);
    }

    public function test_forgot_password_sends_link_with_same_message_for_unknown_email(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'rio@alita.id']);
        $message = 'Jika email terdaftar, link reset password sudah kami kirim. Link berlaku 60 menit.';

        $this->post('/admin/lupa-password', ['email' => 'rio@alita.id'])->assertSessionHas('status', $message);
        $this->post('/admin/lupa-password', ['email' => 'tidak.ada@alita.id'])->assertSessionHas('status', $message);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $n) use ($user): bool {
            return str_contains($n->toMail($user)->actionUrl, '/admin/reset-password/');
        });
        Notification::assertSentTimes(ResetPasswordNotification::class, 1);
    }

    public function test_password_can_be_reset_with_valid_token_only(): void
    {
        $user = User::factory()->create(['email' => 'rio@alita.id']);
        $token = Password::createToken($user);

        $this->post('/admin/reset-password/salah-token', ['email' => 'rio@alita.id', 'password' => 'passwordbaru123', 'password_confirmation' => 'passwordbaru123'])
            ->assertSessionHasErrors('email');

        $this->post("/admin/reset-password/{$token}", ['email' => 'rio@alita.id', 'password' => 'passwordbaru123', 'password_confirmation' => 'passwordbaru123'])
            ->assertRedirect(route('admin.login'));

        $this->assertTrue(Hash::check('passwordbaru123', (string) $user->fresh()?->password));
        $this->assertDatabaseHas('activity_logs', ['action' => 'auth.password_reset', 'subject_id' => $user->id]);
    }

    public function test_user_can_change_own_password_with_current_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/admin/profil/password', ['current_password' => 'keliru', 'password' => 'passwordbaru123', 'password_confirmation' => 'passwordbaru123'])
            ->assertSessionHasErrors('current_password');

        $this->actingAs($user)
            ->put('/admin/profil/password', ['current_password' => self::PASSWORD, 'password' => 'pendek', 'password_confirmation' => 'pendek'])
            ->assertSessionHasErrors('password');

        $this->actingAs($user)
            ->put('/admin/profil/password', ['current_password' => self::PASSWORD, 'password' => 'passwordbaru123', 'password_confirmation' => 'passwordbaru123'])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('passwordbaru123', (string) $user->fresh()?->password));
    }

    public function test_role_middleware_blocks_agent_from_admin_only_routes(): void
    {
        Route::middleware(['web', 'auth', 'active', 'role:admin'])->get('/_test/admin-only', fn () => 'ok');

        $this->actingAs(User::factory()->create())->get('/_test/admin-only')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/_test/admin-only')->assertOk();
    }

    public function test_agent_does_not_see_admin_only_menu(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertOk()->assertSee('Dashboard')->assertDontSee('Audit log');
    }
}
