<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_and_json_never_expose_personal_data(): void
    {
        $agent = User::factory()->create(['name' => 'Agen Rahasia']);
        $ticket = Ticket::factory()->create([
            'requester_name' => 'Budi Santoso',
            'requester_email' => 'budi.rahasia@alita.id',
            'description' => 'Deskripsi sangat pribadi',
            'assigned_to' => $agent->id,
        ]);

        foreach ([$this->get('/antrian'), $this->getJson('/antrian/data')] as $response) {
            $response->assertOk()
                ->assertSee($ticket->ticket_no)
                ->assertDontSee('Budi Santoso')
                ->assertDontSee('budi.rahasia@alita.id')
                ->assertDontSee('Deskripsi sangat pribadi')
                ->assertDontSee('Agen Rahasia');
        }
    }

    public function test_json_lists_active_tickets_in_queue_order_with_summary(): void
    {
        $medium = Ticket::factory()->create();
        $waiting = Ticket::factory()->status(TicketStatus::Menunggu)->create();
        $urgent = Ticket::factory()->status(TicketStatus::Diproses)->priority(TicketPriority::Urgent)->create();
        $closed = Ticket::factory()->status(TicketStatus::Ditutup)->create();
        Ticket::factory()->status(TicketStatus::Selesai)->create(['resolved_at' => now()]);

        $response = $this->getJson('/antrian/data')->assertOk()->assertJsonStructure(['data' => ['summary', 'rows', 'more'], 'meta' => ['updated_at']]);

        $this->assertSame(
            [[$urgent->ticket_no, 1], [$medium->ticket_no, 2], [$waiting->ticket_no, null]],
            array_map(fn (array $row): array => [$row['ticket_no'], $row['position']], $response->json('data.rows')),
        );
        $this->assertNotContains($closed->ticket_no, array_column($response->json('data.rows'), 'ticket_no'));
        $response->assertJsonPath('data.summary', ['baru' => 1, 'diproses' => 1, 'menunggu' => 1, 'selesai_hari_ini' => 1]);
    }

    public function test_category_filter_keeps_global_positions(): void
    {
        Ticket::factory()->create();
        $apps = Ticket::factory()->itApps()->create();

        $this->getJson('/antrian/data?kategori=itapps')
            ->assertJsonCount(1, 'data.rows')
            ->assertJsonPath('data.rows.0.ticket_no', $apps->ticket_no)
            ->assertJsonPath('data.rows.0.position', 2);
    }

    public function test_board_is_limited_and_reports_remaining_count(): void
    {
        config(['helpdesk.queue_board_limit' => 2]);
        Ticket::factory()->count(3)->create();

        $this->getJson('/antrian/data')->assertJsonCount(2, 'data.rows')->assertJsonPath('data.more', 1);
    }

    public function test_search_highlights_matching_row(): void
    {
        $ticket = Ticket::factory()->create();

        $this->get('/antrian?cari='.strtolower((string) $ticket->ticket_no))->assertSee('qrow is-match', false);
    }
}
