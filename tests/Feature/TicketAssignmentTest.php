<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Jobs\SendTicketEmail;
use App\Mail\TicketAssignedMail;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TicketAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    public function test_agent_can_take_unassigned_ticket_and_it_moves_to_in_progress(): void
    {
        $agent = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($agent)->post(route('admin.tickets.take', $ticket))->assertSessionHasNoErrors();

        $ticket->refresh();
        $this->assertSame($agent->id, $ticket->assigned_to);
        $this->assertSame(TicketStatus::Diproses, $ticket->status);
        $this->assertNotNull($ticket->first_response_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'ticket.assigned', 'actor_id' => $agent->id]);
        Queue::assertNotPushed(SendTicketEmail::class, fn (SendTicketEmail $job): bool => $job->mailable instanceof TicketAssignedMail);
    }

    public function test_agent_cannot_take_ticket_handled_by_someone_else(): void
    {
        $other = User::factory()->create();
        $ticket = Ticket::factory()->status(TicketStatus::Diproses)->create(['assigned_to' => $other->id]);

        $this->actingAs(User::factory()->create())->post(route('admin.tickets.take', $ticket))->assertForbidden();

        $this->assertSame($other->id, $ticket->fresh()?->assigned_to);
    }

    public function test_agent_cannot_assign_ticket_to_other_user(): void
    {
        $agent = User::factory()->create();
        $colleague = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($agent)
            ->patch(route('admin.tickets.assign', $ticket), ['assigned_to' => $colleague->id])
            ->assertForbidden();

        $this->assertNull($ticket->fresh()?->assigned_to);
    }

    public function test_admin_can_assign_and_assignee_is_emailed(): void
    {
        $admin = User::factory()->admin()->create();
        $agent = User::factory()->create(['email' => 'agent@alita.id']);
        $ticket = Ticket::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.tickets.assign', $ticket), ['assigned_to' => $agent->id])
            ->assertSessionHasNoErrors();

        $this->assertSame($agent->id, $ticket->fresh()?->assigned_to);
        $this->assertDatabaseHas('email_logs', ['to_email' => 'agent@alita.id', 'type' => 'agent_ticket_assigned']);
        Queue::assertPushed(SendTicketEmail::class, fn (SendTicketEmail $job): bool => $job->mailable instanceof TicketAssignedMail);
    }

    public function test_admin_cannot_assign_to_inactive_user(): void
    {
        $admin = User::factory()->admin()->create();
        $inactive = User::factory()->inactive()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.tickets.assign', $ticket), ['assigned_to' => $inactive->id])
            ->assertSessionHasErrors('assigned_to');
    }

    public function test_admin_can_unassign(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = Ticket::factory()->status(TicketStatus::Diproses)->create(['assigned_to' => $admin->id]);

        $this->actingAs($admin)->patch(route('admin.tickets.assign', $ticket), ['assigned_to' => ''])->assertSessionHasNoErrors();

        $this->assertNull($ticket->fresh()?->assigned_to);
    }
}
