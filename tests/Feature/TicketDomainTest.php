<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_number_uses_creation_date_and_padded_id(): void
    {
        $this->travelTo('2026-09-24 10:00:00');

        $ticket = Ticket::factory()->create();

        $this->assertSame(sprintf('IT-20260924-%05d', $ticket->id), $ticket->ticket_no);
        $this->assertMatchesRegularExpression('/^IT-\d{8}-\d{5,}$/', (string) $ticket->fresh()?->ticket_no);
    }

    public function test_new_ticket_records_initial_history_and_activity_time(): void
    {
        $ticket = Ticket::factory()->create();

        $this->assertNotNull($ticket->last_activity_at);
        $this->assertDatabaseHas('ticket_status_histories', [
            'ticket_id' => $ticket->id,
            'from_status' => null,
            'to_status' => TicketStatus::Baru->value,
            'changed_by' => null,
            'actor_label' => 'Pemohon',
        ]);
    }

    public function test_type_label_shows_free_text_for_others(): void
    {
        $ticket = Ticket::factory()->itApps()->create(['module_other' => 'Modul absensi']);
        $ticket->module?->update(['is_other' => true]);

        $this->assertSame('Modul absensi', $ticket->load('service', 'module')->typeLabel());
    }
}
