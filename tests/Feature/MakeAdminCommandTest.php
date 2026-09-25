<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\PendingCommand;
use Tests\TestCase;

class MakeAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_active_admin_and_audit_log(): void
    {
        $this->runCommand('Rio', 'Rio@Alita.id', 'rahasia-kuat-123')->assertSuccessful();

        $user = User::query()->where('email', 'rio@alita.id')->firstOrFail();
        $this->assertSame(UserRole::Admin, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('rahasia-kuat-123', $user->password));
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.created',
            'actor_id' => null,
            'actor_label' => 'Sistem',
            'subject_id' => $user->id,
        ]);
    }

    public function test_command_rejects_short_password(): void
    {
        $this->runCommand('Rio', 'rio@alita.id', 'pendek')->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_command_rejects_existing_email(): void
    {
        User::factory()->create(['email' => 'rio@alita.id']);

        $this->runCommand('Rio', 'rio@alita.id', 'rahasia-kuat-123')->assertFailed();

        $this->assertDatabaseCount('users', 1);
    }

    private function runCommand(string $name, string $email, string $password): PendingCommand
    {
        $command = $this->artisan('helpdesk:make-admin');
        assert($command instanceof PendingCommand);

        return $command
            ->expectsQuestion('Nama', $name)
            ->expectsQuestion('Email', $email)
            ->expectsQuestion('Password (minimal 10 karakter)', $password)
            ->expectsQuestion('Ulangi password', $password);
    }
}
