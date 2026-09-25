<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Dasar semua email tiket: subjeknya dibaca TicketNotifier untuk email_logs. */
abstract class TicketMail extends Mailable
{
    use SerializesModels;

    abstract public function envelope(): Envelope;
}
