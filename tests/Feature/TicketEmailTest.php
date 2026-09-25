<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EmailStatus;
use App\Enums\TicketStatus;
use App\Jobs\SendTicketEmail;
use App\Mail\NewTicketAdminMail;
use App\Mail\RequesterRepliedMail;
use App\Mail\TicketAssignedMail;
use App\Mail\TicketCommentMail;
use App\Mail\TicketCreatedMail;
use App\Mail\TicketStatusChangedMail;
use App\Mail\TrackingLinkMail;
use App\Models\Category;
use App\Models\EmailLog;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\HelpdeskSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class TicketEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HelpdeskSeeder::class);
        config(['helpdesk.admin_emails' => ['rio@alita.id', 'it@alita.id']]);
    }

    public function test_new_ticket_queues_email_to_every_admin_and_requester(): void
    {
        Queue::fake();

        $this->submitTicket();

        $this->assertSame(
            ['it@alita.id', 'rio@alita.id', 'siti@alita.id'],
            EmailLog::query()->where('status', EmailStatus::Queued)->orderBy('to_email')->pluck('to_email')->all(),
        );
        $this->assertDatabaseHas('email_logs', ['to_email' => 'rio@alita.id', 'type' => 'admin_new_ticket']);
        $this->assertDatabaseHas('email_logs', ['to_email' => 'siti@alita.id', 'type' => 'requester_ticket_created']);
        Queue::assertPushed(SendTicketEmail::class, 3);
        Queue::assertPushed(SendTicketEmail::class, fn (SendTicketEmail $job): bool => $job->mailable instanceof NewTicketAdminMail
            && $job->mailable->envelope()->replyTo[0]->address === 'siti@alita.id');
    }

    public function test_job_sends_email_and_marks_log_sent(): void
    {
        Mail::fake();

        $this->submitTicket();

        $this->assertSame(3, EmailLog::query()->where('status', EmailStatus::Sent)->whereNotNull('sent_at')->where('attempts', 1)->count());
        Mail::assertSent(NewTicketAdminMail::class, fn (NewTicketAdminMail $mail): bool => $mail->hasTo('rio@alita.id')
            && str_starts_with((string) $mail->envelope()->subject, '[Tiket baru] IT-'));
        Mail::assertSent(TicketCreatedMail::class, fn (TicketCreatedMail $mail): bool => $mail->hasTo('siti@alita.id'));
    }

    public function test_failed_job_marks_log_failed(): void
    {
        Queue::fake();
        $this->submitTicket();
        $log = EmailLog::query()->where('type', 'requester_ticket_created')->sole();
        $job = Queue::pushed(SendTicketEmail::class)->first(fn (SendTicketEmail $job): bool => $job->emailLogId === $log->id);

        $job->failed(new RuntimeException('SMTP tidak bisa dihubungi'));

        $this->assertSame(EmailStatus::Failed, $log->fresh()?->status);
        $this->assertSame('SMTP tidak bisa dihubungi', $log->fresh()?->error_message);
    }

    public function test_requester_text_is_escaped_in_email(): void
    {
        Queue::fake();
        $this->submitTicket('<script>alert(1)</script> laptop mati');
        $ticket = Ticket::query()->with(['category', 'service', 'module', 'attachments'])->sole();

        $html = (new NewTicketAdminMail($ticket, 1, route('admin.tickets.show', $ticket)))->render();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /** Kirim sungguhan lewat mailer "array" (bukan fake) agar properti yang bentrok dengan Mailable ikut tertangkap. */
    public function test_every_ticket_mail_builds_a_real_message(): void
    {
        Queue::fake();
        $this->submitTicket();
        $ticket = Ticket::query()->with(['category', 'service', 'module', 'attachments'])->sole();
        $agent = User::factory()->create();
        $comment = $ticket->comments()->create(['user_id' => $agent->id, 'author_type' => 'agent', 'body' => 'Halo', 'is_internal' => false]);
        $url = 'https://helpdesk.test/x';

        $mails = [
            new NewTicketAdminMail($ticket, 1, $url),
            new TicketCreatedMail($ticket, 1, $url),
            new TicketStatusChangedMail($ticket, TicketStatus::Baru, TicketStatus::Diproses, 'Catatan', $url),
            new TicketCommentMail($ticket, $comment, $url),
            new RequesterRepliedMail($ticket, $comment, $url),
            new TicketAssignedMail($ticket, $url),
            new TrackingLinkMail($ticket, $url),
        ];

        foreach ($mails as $mail) {
            $sent = Mail::mailer('array')->to('tujuan@alita.id')->send($mail);
            $this->assertNotNull($sent, $mail::class);
            $this->assertSame(config('mail.from.address'), $sent->getOriginalMessage()->getFrom()[0]->getAddress(), $mail::class);
        }
    }

    private function submitTicket(string $description = 'Printer lantai 3 tidak bisa mencetak.'): void
    {
        $this->post('/tiket', [
            'category_id' => Category::query()->where('code', Category::ITINFRA)->value('id'),
            'service_id' => Service::query()->where('name', 'Printer')->value('id'),
            'requester_name' => 'Siti',
            'requester_email' => 'siti@alita.id',
            'description' => $description,
        ])->assertRedirect(route('tickets.submitted'));
    }
}
