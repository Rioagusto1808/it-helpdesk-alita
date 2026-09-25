<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Users\ToggleUserActive;
use App\Actions\Users\UpdateUser;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_user_with_initial_password(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), ['name' => 'Dewi', 'email' => 'Dewi@Alita.id', 'role' => 'agent', 'password' => 'passwordawal123'])
            ->assertRedirect(route('admin.users.index'));

        $user = User::query()->where('email', 'dewi@alita.id')->sole();
        $this->assertSame(UserRole::Agent, $user->role);
        $this->assertTrue(Hash::check('passwordawal123', $user->password));
        $this->assertDatabaseHas('activity_logs', ['action' => 'user.created', 'actor_id' => $admin->id, 'subject_id' => $user->id]);
    }

    public function test_empty_password_sends_set_password_link(): void
    {
        Notification::fake();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.users.store'), ['name' => 'Budi', 'email' => 'budi@alita.id', 'role' => 'agent', 'password' => ''])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo(User::query()->where('email', 'budi@alita.id')->sole(), ResetPasswordNotification::class);
    }

    public function test_admin_cannot_deactivate_or_demote_self(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create();

        $this->actingAs($admin)->patch(route('admin.users.toggle', $admin))->assertSessionHasErrors('user');
        $this->actingAs($admin)->put(route('admin.users.update', $admin), ['name' => $admin->name, 'email' => $admin->email, 'role' => 'agent'])
            ->assertSessionHasErrors('role');

        $fresh = $admin->fresh();
        $this->assertTrue($fresh?->is_active);
        $this->assertSame(UserRole::Admin, $fresh?->role);
    }

    public function test_system_always_keeps_one_active_admin(): void
    {
        // Admin kedua nonaktif: $only adalah satu-satunya admin aktif.
        $only = User::factory()->admin()->create();
        $inactiveAdmin = User::factory()->admin()->inactive()->create();

        // Diuji langsung di Action karena admin tidak bisa mengubah dirinya sendiri lewat UI.
        $this->expectException(ValidationException::class);
        app(ToggleUserActive::class)->handle($only, $inactiveAdmin);
    }

    public function test_admin_can_deactivate_another_admin_while_one_remains(): void
    {
        $admin = User::factory()->admin()->create();
        $second = User::factory()->admin()->create();

        $this->actingAs($admin)->patch(route('admin.users.toggle', $second))->assertSessionHasNoErrors();
        $this->assertFalse($second->fresh()?->is_active);
        $this->assertDatabaseHas('activity_logs', ['action' => 'user.toggled', 'subject_id' => $second->id]);

        // Satu-satunya admin aktif tersisa tidak bisa diturunkan perannya oleh siapa pun.
        $this->expectException(ValidationException::class);
        app(UpdateUser::class)->handle($admin, $admin->name, $admin->email, UserRole::Agent, null, $second);
    }

    public function test_update_can_change_password_and_log_never_contains_it(): void
    {
        $admin = User::factory()->admin()->create();
        $agent = User::factory()->create();

        $this->actingAs($admin)->put(route('admin.users.update', $agent), [
            'name' => 'Nama baru', 'email' => $agent->email, 'role' => 'agent', 'password' => 'rahasiabaru123',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('rahasiabaru123', (string) $agent->fresh()?->password));
        $log = ActivityLog::query()->where('action', 'user.updated')->sole();
        $this->assertStringNotContainsString('rahasiabaru123', (string) json_encode($log->properties));
        $this->assertContains('password', $log->properties['changed'] ?? []);
    }
}
