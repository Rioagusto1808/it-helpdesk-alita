<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\EmailStatus;
use App\Mail\TicketMail;
use App\Models\EmailLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

final class SendTicketEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> detik: coba ulang setelah 1 menit, lalu 5 menit */
    public array $backoff = [60, 300];

    public function __construct(public readonly int $emailLogId, public readonly TicketMail $mailable) {}

    public function handle(): void
    {
        $log = EmailLog::query()->findOrFail($this->emailLogId);
        $log->increment('attempts');

        Mail::to($log->to_email)->send($this->mailable);

        $log->update(['status' => EmailStatus::Sent, 'sent_at' => now(), 'error_message' => null]);
    }

    public function failed(Throwable $e): void
    {
        EmailLog::query()->whereKey($this->emailLogId)->update([
            'status' => EmailStatus::Failed,
            'error_message' => Str::limit($e->getMessage(), 1000, ''),
        ]);

        Log::error('Email tiket gagal dikirim.', ['email_log_id' => $this->emailLogId]);
    }
}
