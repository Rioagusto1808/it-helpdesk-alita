<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_counts_chart_and_tables(): void
    {
        $agent = User::factory()->create();
        Ticket::factory()->count(2)->create();
        $mine = Ticket::factory()->status(TicketStatus::Diproses)->create(['assigned_to' => $agent->id]);
        Ticket::factory()->itApps()->status(TicketStatus::Menunggu)->create();
        Ticket::factory()->status(TicketStatus::Selesai)->create(['resolved_at' => now()]);
        Ticket::factory()->priority(TicketPriority::Urgent)->create(['created_at' => now()->subHours(2)]);

        $this->actingAs($agent)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Tiket masuk 14 hari terakhir')
            ->assertSee('Antrian saat ini')
            ->assertSee($mine->ticket_no)
            ->assertSee('<svg class="chart-svg"', false);

        $this->getJson(route('admin.dashboard.data'))
            ->assertOk()
            ->assertJsonPath('data.baru', 3)
            ->assertJsonPath('data.diproses', 1)
            ->assertJsonPath('data.menunggu', 1)
            ->assertJsonPath('data.selesai_hari_ini', 1)
            ->assertJsonPath('data.lewat_sla', 1)
            ->assertJsonStructure(['data' => ['avg_response_minutes', 'avg_resolve_minutes'], 'meta' => ['updated_at']]);
    }

    public function test_average_response_time_is_in_minutes(): void
    {
        Ticket::factory()->create(['created_at' => now()->subHours(2)])->forceFill(['first_response_at' => now()->subHour()])->save();

        $this->actingAs(User::factory()->create())->getJson(route('admin.dashboard.data'))->assertJsonPath('data.avg_response_minutes', 60);
    }
}
