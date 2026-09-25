<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Jobs\SendTicketEmail;
use App\Mail\TicketStatusChangedMail;
use App\Models\Ticket;
use App\Support\TrackingUrl;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AutoCloseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        config(['helpdesk.auto_close_days' => 3]);
    }

    public function test_resolved_ticket_older_than_limit_is_closed_by_system(): void
    {
        $old = Ticket::factory()->status(TicketStatus::Selesai)->create(['resolved_at' => now()->subDays(4)]);
        $recent = Ticket::factory()->status(TicketStatus::Selesai)->create(['resolved_at' => now()->subDays(2)]);

        $this->artisan('helpdesk:auto-close')->expectsOutput('1 tiket ditutup otomatis.')->assertSuccessful();

        $this->assertSame(TicketStatus::Ditutup, $old->fresh()?->status);
        $this->assertNotNull($old->fresh()?->closed_at);
        $this->assertSame(TicketStatus::Selesai, $recent->fresh()?->status);
        $this->assertDatabaseHas('ticket_status_histories', ['ticket_id' => $old->id, 'to_status' => 'ditutup', 'actor_label' => 'Sistem', 'changed_by' => null]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'ticket.status_changed', 'subject_id' => $old->id, 'actor_label' => 'Sistem']);
        Queue::assertPushed(SendTicketEmail::class, fn (SendTicketEmail $job): bool => $job->mailable instanceof TicketStatusChangedMail);
    }

    public function test_ticket_replied_by_requester_is_not_closed(): void
    {
        $ticket = Ticket::factory()->status(TicketStatus::Selesai)->create(['resolved_at' => now()->subDays(5)]);

        $this->post(TrackingUrl::make($ticket, 'tracking.reply'), ['body' => 'Masih error setelah dicoba.']);
        $this->artisan('helpdesk:auto-close')->assertSuccessful();

        $this->assertSame(TicketStatus::Diproses, $ticket->fresh()?->status);
    }

    public function test_other_statuses_are_untouched(): void
    {
        $waiting = Ticket::factory()->status(TicketStatus::Menunggu)->create(['resolved_at' => now()->subDays(10)]);

        $this->artisan('helpdesk:auto-close')->expectsOutput('0 tiket ditutup otomatis.');

        $this->assertSame(TicketStatus::Menunggu, $waiting->fresh()?->status);
    }

    public function test_command_is_scheduled_daily_at_one_am(): void
    {
        $event = collect(app(Schedule::class)->events())->first(fn ($e): bool => str_contains((string) $e->command, 'helpdesk:auto-close'));

        $this->assertNotNull($event);
        $this->assertSame('0 1 * * *', $event->expression);
    }
}
