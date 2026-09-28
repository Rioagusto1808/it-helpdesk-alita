<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Jobs\SendTicketEmail;
use App\Mail\TicketCommentMail;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Modal balasan di daftar tiket admin: status (diproses/selesai/ditolak) + deskripsi + lampiran → satu email. */
class TicketResponseTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Storage::fake('local');
        $this->agent = User::factory()->create(['name' => 'Rio']);
    }

    public function test_list_renders_respond_modal_with_ticket_data(): void
    {
        $ticket = Ticket::factory()->create(['requester_email' => 'siti@alita.id', 'description' => 'Printer macet']);

        $this->actingAs($this->agent)->get(route('admin.tickets.index'))
            ->assertOk()
            ->assertSee('data-respond-dialog', false)
            ->assertSee('data-ticket-id="'.$ticket->id.'"', false)
            ->assertSee(route('admin.tickets.respond', $ticket))
            ->assertSee('Ditolak');
    }

    public function test_done_with_attachment_changes_status_and_sends_one_email_with_file(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($this->agent)
            ->from(route('admin.tickets.index'))
            ->post(route('admin.tickets.respond', $ticket), [
                'status' => 'selesai',
                'message' => 'Driver printer sudah diperbarui.',
                'attachment' => UploadedFile::fake()->create('panduan.pdf', 20, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.tickets.index'))
            ->assertSessionHasNoErrors();

        $fresh = $ticket->fresh();
        $this->assertSame(TicketStatus::Selesai, $fresh?->status);
        $this->assertNotNull($fresh?->resolved_at);
        $this->assertNotNull($fresh?->first_response_at);

        $comment = $fresh?->comments()->sole();
        $this->assertFalse($comment?->is_internal);
        $this->assertSame('Driver printer sudah diperbarui.', $comment?->body);
        $attachment = $comment?->attachments()->sole();
        Storage::disk('local')->assertExists((string) $attachment?->stored_path);

        $this->assertDatabaseHas('ticket_status_histories', ['ticket_id' => $ticket->id, 'from_status' => 'baru', 'to_status' => 'selesai', 'changed_by' => $this->agent->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'ticket.status_changed', 'subject_id' => $ticket->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'ticket.comment_added', 'subject_id' => $ticket->id]);

        Queue::assertPushed(SendTicketEmail::class, 1);
        Queue::assertPushed(SendTicketEmail::class, function (SendTicketEmail $job): bool {
            $mail = $job->mailable;

            return $mail instanceof TicketCommentMail
                && $mail->newStatus === TicketStatus::Selesai
                && count($mail->attachments()) === 1
                && str_contains((string) $mail->envelope()->subject, 'Selesai');
        });
    }

    public function test_detail_page_only_shows_simple_reply_form(): void
    {
        $ticket = Ticket::factory()->status(TicketStatus::Diproses)->create();

        $this->actingAs($this->agent)->get(route('admin.tickets.show', $ticket))
            ->assertOk()
            ->assertSee(route('admin.tickets.respond', $ticket))
            ->assertSee('Balas tiket')
            ->assertDontSee(route('admin.tickets.status', $ticket))
            ->assertDontSee(route('admin.tickets.priority', $ticket))
            ->assertDontSee(route('admin.tickets.comments.store', $ticket))
            ->assertDontSee('Catatan internal');
    }

    public function test_first_responder_becomes_assignee(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($this->agent)
            ->post(route('admin.tickets.respond', $ticket), ['status' => 'diproses', 'message' => 'Kami cek dulu.'])
            ->assertSessionHasNoErrors();

        $this->assertSame($this->agent->id, $ticket->fresh()?->assigned_to);
        $this->assertDatabaseHas('activity_logs', ['action' => 'ticket.assigned', 'subject_id' => $ticket->id, 'actor_id' => $this->agent->id]);

        // Sudah ada petugas: balasan dari orang lain tidak mengambil alih.
        $other = User::factory()->create();
        $this->actingAs($other)->post(route('admin.tickets.respond', $ticket), ['status' => 'selesai', 'message' => 'Beres.']);
        $this->assertSame($this->agent->id, $ticket->fresh()?->assigned_to);
    }

    public function test_reply_with_same_status_only_adds_comment(): void
    {
        $ticket = Ticket::factory()->status(TicketStatus::Diproses)->create();

        $this->actingAs($this->agent)
            ->post(route('admin.tickets.respond', $ticket), ['status' => 'diproses', 'message' => 'Sedang kami cek.'])
            ->assertSessionHasNoErrors();

        $this->assertSame(TicketStatus::Diproses, $ticket->fresh()?->status);
        $this->assertDatabaseMissing('ticket_status_histories', ['ticket_id' => $ticket->id, 'changed_by' => $this->agent->id]);
        $this->assertDatabaseHas('ticket_comments', ['ticket_id' => $ticket->id, 'body' => 'Sedang kami cek.']);
        Queue::assertPushed(SendTicketEmail::class, fn (SendTicketEmail $job): bool => $job->mailable instanceof TicketCommentMail && $job->mailable->newStatus === null);
    }

    public function test_reject_in_progress_ticket_closes_it(): void
    {
        $ticket = Ticket::factory()->status(TicketStatus::Diproses)->create();

        $this->actingAs($this->agent)
            ->post(route('admin.tickets.respond', $ticket), ['status' => 'dibatalkan', 'message' => 'Bukan kendala IT, silakan hubungi GA.'])
            ->assertSessionHasNoErrors();

        $this->assertSame(TicketStatus::Dibatalkan, $ticket->fresh()?->status);
        $this->assertDatabaseHas('ticket_comments', ['ticket_id' => $ticket->id, 'body' => 'Bukan kendala IT, silakan hubungi GA.']);
    }

    public function test_validation_and_disallowed_status(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($this->agent)
            ->post(route('admin.tickets.respond', $ticket), ['status' => 'ditutup', 'message' => ''])
            ->assertSessionHasErrors(['status', 'message']);

        $this->post(route('admin.tickets.respond', $ticket), [
            'status' => 'selesai',
            'message' => 'x',
            'attachment' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'),
        ])->assertSessionHasErrors('attachment');

        // Selesai → Ditolak tidak diizinkan; tidak ada komentar maupun file yang tertinggal.
        $resolved = Ticket::factory()->status(TicketStatus::Selesai)->create();
        $this->post(route('admin.tickets.respond', $resolved), [
            'status' => 'dibatalkan',
            'message' => 'Tolak',
            'attachment' => UploadedFile::fake()->create('a.pdf', 5, 'application/pdf'),
        ])->assertSessionHasErrors('status');

        $this->assertDatabaseCount('ticket_comments', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
        Queue::assertNothingPushed();
    }

    public function test_final_ticket_and_guest_are_rejected(): void
    {
        $ticket = Ticket::factory()->create();
        $this->post(route('admin.tickets.respond', $ticket), ['status' => 'selesai', 'message' => 'x'])->assertRedirect(route('admin.login'));
        $this->assertSame(TicketStatus::Baru, $ticket->fresh()?->status);

        $closed = Ticket::factory()->status(TicketStatus::Ditutup)->create();
        $this->actingAs($this->agent)
            ->post(route('admin.tickets.respond', $closed), ['status' => 'diproses', 'message' => 'Buka lagi'])
            ->assertForbidden();
    }
}
