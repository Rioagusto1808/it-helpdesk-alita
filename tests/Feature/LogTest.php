<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Tickets\ChangeTicketStatus;
use App\Enums\EmailStatus;
use App\Enums\TicketStatus;
use App\Jobs\SendTicketEmail;
use App\Mail\TicketStatusChangedMail;
use App\Models\ActivityLog;
use App\Models\EmailLog;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LogTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_log_lists_and_filters_by_ticket_number(): void
    {
        $admin = User::factory()->admin()->create();
        $a = Ticket::factory()->create();
        $b = Ticket::factory()->create();
        ActivityLog::record('ticket.created', $a, ['catatan' => 'log-a']);
        ActivityLog::record('ticket.created', $b, ['catatan' => 'log-b']);

        $this->actingAs($admin)->get(route('admin.logs.activity'))->assertOk()->assertSee('log-a')->assertSee('log-b');
        $this->get(route('admin.logs.activity', ['tiket' => $a->ticket_no]))->assertSee('log-a')->assertDontSee('log-b');
    }

    public function test_failed_email_can_be_retried_with_original_status_change(): void
    {
        Queue::fake();
        $admin = User::factory()->admin()->create();
        $ticket = Ticket::factory()->create();
        app(ChangeTicketStatus::class)->handle($ticket, TicketStatus::Diproses, $admin);
        $log = EmailLog::query()->where('type', 'requester_status_changed')->sole();
        $log->update(['status' => EmailStatus::Failed, 'error_message' => 'SMTP timeout']);
        Queue::fake(); // abaikan job pengiriman pertama

        $this->actingAs($admin)->post(route('admin.logs.email.retry', $log))->assertSessionHasNoErrors();

        $this->assertSame(EmailStatus::Queued, $log->fresh()?->status);
        $this->assertNull($log->fresh()?->error_message);
        $this->assertDatabaseHas('activity_logs', ['action' => 'email.retried', 'actor_id' => $admin->id]);
        Queue::assertPushed(SendTicketEmail::class, fn (SendTicketEmail $job): bool => $job->emailLogId === $log->id
            && $job->mailable instanceof TicketStatusChangedMail
            && $job->mailable->previousStatus === TicketStatus::Baru
            && $job->mailable->newStatus === TicketStatus::Diproses);
    }

    public function test_only_failed_email_can_be_retried(): void
    {
        $admin = User::factory()->admin()->create();
        $log = EmailLog::query()->create(['ticket_id' => Ticket::factory()->create()->id, 'to_email' => 'a@alita.id', 'subject' => 'x', 'type' => 'admin_new_ticket', 'status' => 'sent']);

        $this->actingAs($admin)->post(route('admin.logs.email.retry', $log))->assertSessionHasErrors('email');
    }

    public function test_email_of_deleted_ticket_cannot_be_retried(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = Ticket::factory()->create();
        $log = EmailLog::query()->create(['ticket_id' => $ticket->id, 'to_email' => 'a@alita.id', 'subject' => 'x', 'type' => 'admin_new_ticket', 'status' => 'failed']);
        $ticket->delete();

        $this->actingAs($admin)->post(route('admin.logs.email.retry', $log))->assertSessionHasErrors('email');
    }

    public function test_email_log_filters_by_status(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = Ticket::factory()->create();
        EmailLog::query()->create(['ticket_id' => $ticket->id, 'to_email' => 'gagal@alita.id', 'subject' => 'x', 'type' => 'admin_new_ticket', 'status' => 'failed']);
        EmailLog::query()->create(['ticket_id' => $ticket->id, 'to_email' => 'terkirim@alita.id', 'subject' => 'x', 'type' => 'admin_new_ticket', 'status' => 'sent']);

        $this->actingAs($admin)->get(route('admin.logs.email', ['status' => 'failed']))
            ->assertOk()->assertSee('gagal@alita.id')->assertDontSee('terkirim@alita.id')->assertSee('Kirim ulang');
    }
}
