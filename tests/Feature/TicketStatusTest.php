<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Jobs\SendTicketEmail;
use App\Mail\TicketStatusChangedMail;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TicketStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        $this->agent = User::factory()->create(['name' => 'Rio']);
    }

    /** @return array<string, array{TicketStatus, TicketStatus}> */
    public static function allowedTransitions(): array
    {
        return [
            'baru → diproses' => [TicketStatus::Baru, TicketStatus::Diproses],
            'baru → selesai' => [TicketStatus::Baru, TicketStatus::Selesai],
            'baru → dibatalkan' => [TicketStatus::Baru, TicketStatus::Dibatalkan],
            'diproses → menunggu' => [TicketStatus::Diproses, TicketStatus::Menunggu],
            'diproses → selesai' => [TicketStatus::Diproses, TicketStatus::Selesai],
            'diproses → dibatalkan' => [TicketStatus::Diproses, TicketStatus::Dibatalkan],
            'menunggu → diproses' => [TicketStatus::Menunggu, TicketStatus::Diproses],
            'menunggu → selesai' => [TicketStatus::Menunggu, TicketStatus::Selesai],
            'menunggu → dibatalkan' => [TicketStatus::Menunggu, TicketStatus::Dibatalkan],
            'selesai → diproses' => [TicketStatus::Selesai, TicketStatus::Diproses],
            'selesai → ditutup' => [TicketStatus::Selesai, TicketStatus::Ditutup],
        ];
    }

    #[DataProvider('allowedTransitions')]
    public function test_allowed_transition_succeeds_with_history_audit_and_email(TicketStatus $from, TicketStatus $to): void
    {
        $ticket = Ticket::factory()->status($from)->create();

        $this->actingAs($this->agent)
            ->patch(route('admin.tickets.status', $ticket), ['status' => $to->value, 'note' => 'Catatan tim IT'])
            ->assertSessionHasNoErrors();

        $this->assertSame($to, $ticket->fresh()?->status);
        $this->assertDatabaseHas('ticket_status_histories', [
            'ticket_id' => $ticket->id, 'from_status' => $from->value, 'to_status' => $to->value,
            'changed_by' => $this->agent->id, 'actor_label' => 'Rio', 'note' => 'Catatan tim IT',
        ]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'ticket.status_changed', 'subject_id' => $ticket->id, 'actor_id' => $this->agent->id]);
        Queue::assertPushed(SendTicketEmail::class, fn (SendTicketEmail $job): bool => $job->mailable instanceof TicketStatusChangedMail);
    }

    public function test_forbidden_transition_is_rejected(): void
    {
        $ticket = Ticket::factory()->status(TicketStatus::Baru)->create();

        foreach ([TicketStatus::Ditutup, TicketStatus::Menunggu] as $to) {
            $this->actingAs($this->agent)
                ->patch(route('admin.tickets.status', $ticket), ['status' => $to->value, 'note' => 'x'])
                ->assertSessionHasErrors('status');
        }

        $closed = Ticket::factory()->status(TicketStatus::Ditutup)->create();
        $this->actingAs($this->agent)
            ->patch(route('admin.tickets.status', $closed), ['status' => TicketStatus::Diproses->value])
            ->assertSessionHasErrors('status');

        $this->assertSame(TicketStatus::Baru, $ticket->fresh()?->status);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_note_is_required_for_waiting_resolved_and_cancelled(): void
    {
        $cases = [[TicketStatus::Diproses, TicketStatus::Menunggu], [TicketStatus::Diproses, TicketStatus::Selesai], [TicketStatus::Baru, TicketStatus::Dibatalkan]];

        foreach ($cases as [$from, $to]) {
            $ticket = Ticket::factory()->status($from)->create();

            $this->actingAs($this->agent)
                ->patch(route('admin.tickets.status', $ticket), ['status' => $to->value, 'note' => ''])
                ->assertSessionHasErrors('note');

            $this->assertSame($from, $ticket->fresh()?->status);
        }
    }

    public function test_timestamps_are_filled_automatically(): void
    {
        $ticket = Ticket::factory()->create();
        $this->actingAs($this->agent);

        $this->travelTo(now()->addMinutes(10));
        $this->patch(route('admin.tickets.status', $ticket), ['status' => 'diproses']);
        $firstResponse = $ticket->fresh()?->first_response_at;
        $this->assertNotNull($firstResponse);

        $this->patch(route('admin.tickets.status', $ticket), ['status' => 'selesai', 'note' => 'Driver diperbarui']);
        $this->assertNotNull($ticket->fresh()?->resolved_at);

        $this->patch(route('admin.tickets.status', $ticket), ['status' => 'diproses']);
        $this->assertNull($ticket->fresh()?->resolved_at, 'Dibuka lagi: resolved_at dikosongkan.');

        $this->patch(route('admin.tickets.status', $ticket), ['status' => 'selesai', 'note' => 'Beres']);
        $this->patch(route('admin.tickets.status', $ticket), ['status' => 'ditutup']);

        $fresh = $ticket->fresh();
        $this->assertNotNull($fresh?->closed_at);
        $this->assertEquals($firstResponse, $fresh?->first_response_at, 'first_response_at hanya diisi sekali.');
    }

    public function test_guest_cannot_change_status(): void
    {
        $ticket = Ticket::factory()->create();

        $this->patch(route('admin.tickets.status', $ticket), ['status' => 'diproses'])->assertRedirect(route('admin.login'));
        $this->assertSame(TicketStatus::Baru, $ticket->fresh()?->status);
    }
}
