<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Jobs\SendTicketEmail;
use App\Mail\TicketCommentMail;
use App\Mail\TrackingLinkMail;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/** Daftar, filter, export, detail, prioritas, komentar, kirim link, hapus. */
class TicketManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    public function test_list_shows_active_tickets_by_default_and_filters_work(): void
    {
        $agent = User::factory()->create();
        $mine = Ticket::factory()->status(TicketStatus::Diproses)->create(['assigned_to' => $agent->id, 'requester_name' => 'Siti Aminah']);
        $closed = Ticket::factory()->status(TicketStatus::Ditutup)->create();
        $urgent = Ticket::factory()->priority(TicketPriority::Urgent)->create();

        $this->actingAs($agent)->get(route('admin.tickets.index'))
            ->assertOk()->assertSee($mine->ticket_no)->assertSee($urgent->ticket_no)->assertDontSee($closed->ticket_no);

        $this->get(route('admin.tickets.index', ['petugas' => 'me']))->assertSee($mine->ticket_no)->assertDontSee($urgent->ticket_no);
        $this->get(route('admin.tickets.index', ['prioritas' => 'urgent']))->assertSee($urgent->ticket_no)->assertDontSee($mine->ticket_no);
        $this->get(route('admin.tickets.index', ['status' => 'semua']))->assertSee($closed->ticket_no);
        $this->get(route('admin.tickets.index', ['cari' => 'siti']))->assertSee($mine->ticket_no)->assertDontSee($urgent->ticket_no);
        $this->get(route('admin.tickets.index', ['urut' => 'drop table', 'status' => '<x>']))->assertOk();
    }

    public function test_overdue_filter_matches_sla_marker(): void
    {
        $agent = User::factory()->create();
        $late = Ticket::factory()->priority(TicketPriority::Urgent)->create(['created_at' => now()->subHour()]);
        $fresh = Ticket::factory()->priority(TicketPriority::Urgent)->create();

        $this->assertTrue($late->isOverdue());
        $this->assertFalse($fresh->isOverdue());

        $this->actingAs($agent)->get(route('admin.tickets.index', ['sla' => 1]))
            ->assertSee($late->ticket_no)->assertDontSee($fresh->ticket_no)->assertSee('Lewat SLA');
    }

    public function test_export_is_admin_only_and_neutralises_formulas(): void
    {
        Ticket::factory()->create(['requester_name' => '=HYPERLINK("http://jahat")']);

        $this->actingAs(User::factory()->create())->get(route('admin.tickets.export'))->assertForbidden();

        $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.tickets.export'));
        $response->assertOk()->assertDownload();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Nomor tiket', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }

    public function test_detail_shows_internal_comments_and_email_log_to_agent(): void
    {
        $agent = User::factory()->create();
        $ticket = Ticket::factory()->create();
        $ticket->comments()->create(['user_id' => $agent->id, 'author_type' => 'agent', 'body' => 'Catatan rahasia tim', 'is_internal' => true]);
        $ticket->emailLogs()->create(['to_email' => 'rio@alita.id', 'subject' => 'Subjek', 'type' => 'admin_new_ticket', 'status' => 'failed']);

        $this->actingAs($agent)->get(route('admin.tickets.show', $ticket))
            ->assertOk()->assertSee('Catatan rahasia tim')->assertSee('Internal')->assertSee('Email gagal ke rio@alita.id');
    }

    public function test_public_comment_emails_requester_but_internal_does_not(): void
    {
        $agent = User::factory()->create();
        $ticket = Ticket::factory()->status(TicketStatus::Diproses)->create();

        $this->actingAs($agent)->post(route('admin.tickets.comments.store', $ticket), ['body' => 'Catatan untuk tim', 'visibility' => 'internal']);
        Queue::assertNotPushed(SendTicketEmail::class);

        $this->actingAs($agent)->post(route('admin.tickets.comments.store', $ticket), ['body' => 'Coba restart ya', 'visibility' => 'public']);
        Queue::assertPushed(SendTicketEmail::class, fn (SendTicketEmail $job): bool => $job->mailable instanceof TicketCommentMail);

        $this->assertDatabaseHas('ticket_comments', ['ticket_id' => $ticket->id, 'is_internal' => true, 'user_id' => $agent->id]);
        $this->assertDatabaseHas('ticket_comments', ['ticket_id' => $ticket->id, 'is_internal' => false]);
        $this->assertNotNull($ticket->fresh()?->first_response_at);
    }

    public function test_priority_change_is_logged(): void
    {
        $agent = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($agent)->patch(route('admin.tickets.priority', $ticket), ['priority' => 'tinggi'])->assertSessionHasNoErrors();

        $this->assertSame(TicketPriority::Tinggi, $ticket->fresh()?->priority);
        $this->assertDatabaseHas('activity_logs', ['action' => 'ticket.priority_changed', 'actor_id' => $agent->id]);
    }

    public function test_agent_can_resend_tracking_link(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs(User::factory()->create())->post(route('admin.tickets.send-link', $ticket))->assertSessionHasNoErrors();

        Queue::assertPushed(SendTicketEmail::class, fn (SendTicketEmail $job): bool => $job->mailable instanceof TrackingLinkMail);
    }

    public function test_only_admin_can_soft_delete_ticket(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs(User::factory()->create())->delete(route('admin.tickets.destroy', $ticket))->assertForbidden();
        $this->assertNotSoftDeleted($ticket);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->delete(route('admin.tickets.destroy', $ticket))->assertRedirect(route('admin.tickets.index'));
        $this->assertSoftDeleted($ticket);
        $this->assertDatabaseHas('activity_logs', ['action' => 'ticket.deleted', 'actor_id' => $admin->id]);
        $this->get(route('admin.tickets.show', $ticket))->assertNotFound();
    }
}
