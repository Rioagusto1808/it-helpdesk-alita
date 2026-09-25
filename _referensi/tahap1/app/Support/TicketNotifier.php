<?php

namespace App\Support;

use App\Jobs\SendTicketEmail;
use App\Mail\NewTicketAdminMail;
use App\Mail\TicketCreatedMail;
use App\Models\EmailLog;
use App\Models\Ticket;
use Illuminate\Mail\Mailable;

class TicketNotifier
{
    public function ticketCreated(Ticket $ticket): void
    {
        $ticket->loadMissing(['category', 'service', 'module', 'attachments']);

        foreach (config('helpdesk.admin_emails') as $adminEmail) {
            $this->queue($ticket, $adminEmail, 'admin_new_ticket', new NewTicketAdminMail($ticket));
        }

        $this->queue($ticket, $ticket->requester_email, 'requester_ticket_created', new TicketCreatedMail($ticket));
    }

    /** Catat ke email_logs lalu kirim lewat queue, supaya form tetap cepat. */
    private function queue(Ticket $ticket, string $to, string $type, Mailable $mail): void
    {
        $log = EmailLog::create([
            'ticket_id' => $ticket->id,
            'to_email' => $to,
            'subject' => $mail->envelope()->subject,
            'type' => $type,
            'status' => 'queued',
        ]);

        SendTicketEmail::dispatch($log->id, $mail);
    }
}
