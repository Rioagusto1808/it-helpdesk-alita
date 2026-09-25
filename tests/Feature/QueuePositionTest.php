<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Support\QueuePosition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueuePositionTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_orders_by_priority_then_arrival(): void
    {
        $firstMedium = Ticket::factory()->create();
        $secondMedium = Ticket::factory()->status(TicketStatus::Diproses)->create();
        $low = Ticket::factory()->priority(TicketPriority::Rendah)->create();
        $urgent = Ticket::factory()->priority(TicketPriority::Urgent)->create();

        $this->assertSame(1, QueuePosition::for($urgent));
        $this->assertSame(2, QueuePosition::for($firstMedium));
        $this->assertSame(3, QueuePosition::for($secondMedium));
        $this->assertSame(4, QueuePosition::for($low));
    }

    public function test_tickets_outside_queue_have_no_position_and_are_not_counted(): void
    {
        $waiting = Ticket::factory()->status(TicketStatus::Menunggu)->priority(TicketPriority::Urgent)->create();
        $resolved = Ticket::factory()->status(TicketStatus::Selesai)->create();
        $deleted = Ticket::factory()->priority(TicketPriority::Urgent)->create();
        $deleted->delete();
        $queued = Ticket::factory()->create();

        $this->assertNull(QueuePosition::for($waiting));
        $this->assertNull(QueuePosition::for($resolved));
        $this->assertSame(1, QueuePosition::for($queued));
    }
}
