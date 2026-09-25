<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AuthorType;
use App\Enums\TicketStatus;
use App\Jobs\SendTicketEmail;
use App\Mail\RequesterRepliedMail;
use App\Mail\TrackingLinkMail;
use App\Models\Ticket;
use App\Models\User;
use App\Support\TrackingUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Storage::fake('local');
    }

    public function test_valid_signed_url_opens_tracking_page(): void
    {
        $ticket = Ticket::factory()->create(['description' => 'Printer macet di lantai 3']);

        $this->get(TrackingUrl::make($ticket))
            ->assertOk()
            ->assertSee($ticket->ticket_no)
            ->assertSee('Printer macet di lantai 3')
            ->assertSee('Kirim balasan');
    }

    /** Di balik proxy HTTPS request tiba sebagai http; tanda tangan relatif tetap valid. */
    public function test_link_signed_for_https_still_opens_behind_tls_proxy(): void
    {
        $ticket = Ticket::factory()->create();
        URL::forceScheme('https');
        $link = TrackingUrl::make($ticket);
        URL::forceScheme('http');

        $this->assertStringStartsWith('https://', $link);
        $this->get(str_replace('https://', 'http://', $link))->assertOk()->assertSee($ticket->ticket_no);
    }

    public function test_tampered_or_expired_url_is_forbidden_with_friendly_page(): void
    {
        $ticket = Ticket::factory()->create();
        $other = Ticket::factory()->create();

        $tampered = str_replace((string) $ticket->ticket_no, (string) $other->ticket_no, TrackingUrl::make($ticket));
        $this->get($tampered)->assertForbidden()->assertSee('Minta link baru')->assertDontSee((string) $other->description);

        $expired = TrackingUrl::make($ticket, expires: now()->addMinute());
        $this->travel(2)->minutes();
        $this->get($expired)->assertForbidden();

        $this->get('/lacak/'.$ticket->ticket_no)->assertForbidden();
    }

    public function test_internal_comments_are_never_shown(): void
    {
        $agent = User::factory()->create(['name' => 'Rio']);
        $ticket = Ticket::factory()->create();
        $ticket->comments()->create(['user_id' => $agent->id, 'author_type' => AuthorType::Agent, 'body' => 'Sudah kami cek kabelnya', 'is_internal' => false]);
        $ticket->comments()->create(['user_id' => $agent->id, 'author_type' => AuthorType::Agent, 'body' => 'Catatan rahasia tim', 'is_internal' => true]);

        $this->get(TrackingUrl::make($ticket))
            ->assertSee('Sudah kami cek kabelnya')
            ->assertDontSee('Catatan rahasia tim');
    }

    public function test_reply_moves_waiting_or_resolved_ticket_back_to_in_progress(): void
    {
        foreach ([TicketStatus::Menunggu, TicketStatus::Selesai] as $status) {
            $ticket = Ticket::factory()->status($status)->create(['resolved_at' => now()]);

            $this->post(TrackingUrl::make($ticket, 'tracking.reply'), ['body' => 'Sudah saya restart, masih error.'])
                ->assertRedirect()
                ->assertSessionHasNoErrors();

            $ticket->refresh();
            $this->assertSame(TicketStatus::Diproses, $ticket->status);
            $this->assertNull($ticket->resolved_at);
            $this->assertDatabaseHas('ticket_comments', ['ticket_id' => $ticket->id, 'author_type' => 'requester', 'is_internal' => false]);
            $this->assertDatabaseHas('ticket_status_histories', ['ticket_id' => $ticket->id, 'from_status' => $status->value, 'to_status' => 'diproses', 'actor_label' => 'Pemohon']);
        }

        Queue::assertPushed(SendTicketEmail::class, fn (SendTicketEmail $job): bool => $job->mailable instanceof RequesterRepliedMail);
    }

    public function test_reply_notifies_assignee_and_can_carry_attachment(): void
    {
        $agent = User::factory()->create(['email' => 'agent@alita.id']);
        $ticket = Ticket::factory()->status(TicketStatus::Diproses)->create(['assigned_to' => $agent->id]);

        $this->post(TrackingUrl::make($ticket, 'tracking.reply'), [
            'body' => 'Ini screenshot errornya.',
            'attachment' => UploadedFile::fake()->image('error.png'),
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('email_logs', ['to_email' => 'agent@alita.id', 'type' => 'agent_requester_replied']);
        $attachment = $ticket->attachments()->sole();
        $this->assertNotNull($attachment->comment_id);
        Storage::disk('local')->assertExists($attachment->stored_path);
    }

    public function test_reply_is_rejected_for_closed_ticket(): void
    {
        $ticket = Ticket::factory()->status(TicketStatus::Ditutup)->create();

        $this->post(TrackingUrl::make($ticket, 'tracking.reply'), ['body' => 'Halo?'])->assertSessionHasErrors('body');

        $this->assertDatabaseCount('ticket_comments', 0);
    }

    public function test_reply_is_rate_limited_per_ticket(): void
    {
        $ticket = Ticket::factory()->status(TicketStatus::Diproses)->create();
        $url = TrackingUrl::make($ticket, 'tracking.reply');

        for ($i = 0; $i < 10; $i++) {
            $this->post($url, ['body' => "Balasan {$i}"])->assertRedirect();
        }

        $this->post($url, ['body' => 'Balasan ke-11'])->assertTooManyRequests();
    }

    public function test_requester_can_cancel_only_new_ticket(): void
    {
        $new = Ticket::factory()->create();
        $this->post(TrackingUrl::make($new, 'tracking.cancel'))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(TicketStatus::Dibatalkan, $new->fresh()?->status);

        $inProgress = Ticket::factory()->status(TicketStatus::Diproses)->create();
        $this->post(TrackingUrl::make($inProgress, 'tracking.cancel'))->assertSessionHasErrors('status');
        $this->assertSame(TicketStatus::Diproses, $inProgress->fresh()?->status);
    }

    public function test_lookup_sends_link_only_on_match_and_shows_same_message(): void
    {
        $ticket = Ticket::factory()->create(['requester_email' => 'siti@alita.id']);
        $message = 'Jika data cocok, link tracking sudah dikirim ke email tersebut.';

        $this->followingRedirects()
            ->post('/lacak', ['ticket_no' => strtolower((string) $ticket->ticket_no), 'email' => 'SITI@alita.id'])
            ->assertSee($message);
        Queue::assertPushed(SendTicketEmail::class, 1);
        Queue::assertPushed(SendTicketEmail::class, fn (SendTicketEmail $job): bool => $job->mailable instanceof TrackingLinkMail);

        $this->followingRedirects()
            ->post('/lacak', ['ticket_no' => $ticket->ticket_no, 'email' => 'orang.lain@alita.id'])
            ->assertSee($message);
        Queue::assertPushed(SendTicketEmail::class, 1);
    }

    public function test_attachment_download_requires_signature_and_public_visibility(): void
    {
        $ticket = Ticket::factory()->create();
        Storage::disk('local')->put('attachments/a.pdf', 'isi');
        $public = $ticket->attachments()->create(['uploaded_by' => AuthorType::Requester, 'original_name' => 'surat.pdf', 'stored_path' => 'attachments/a.pdf', 'mime' => 'application/pdf', 'size' => 3]);
        $internal = $ticket->comments()->create(['author_type' => AuthorType::Agent, 'body' => 'internal', 'is_internal' => true]);
        $hidden = $ticket->attachments()->create(['comment_id' => $internal->id, 'uploaded_by' => AuthorType::Agent, 'original_name' => 'rahasia.pdf', 'stored_path' => 'attachments/a.pdf', 'mime' => 'application/pdf', 'size' => 3]);
        $otherTicketFile = Ticket::factory()->create()->attachments()->create(['uploaded_by' => AuthorType::Requester, 'original_name' => 'x.pdf', 'stored_path' => 'attachments/a.pdf', 'mime' => 'application/pdf', 'size' => 3]);

        $this->get(TrackingUrl::make($ticket, 'tracking.attachment', ['attachment' => $public->id]))
            ->assertOk()
            ->assertDownload('surat.pdf');
        $this->get("/lacak/{$ticket->ticket_no}/lampiran/{$public->id}")->assertForbidden();
        $this->get(TrackingUrl::make($ticket, 'tracking.attachment', ['attachment' => $hidden->id]))->assertNotFound();
        $this->get(TrackingUrl::make($ticket, 'tracking.attachment', ['attachment' => $otherTicketFile->id]))->assertNotFound();
    }
}
