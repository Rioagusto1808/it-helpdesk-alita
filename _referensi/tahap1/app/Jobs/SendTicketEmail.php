<?php

namespace App\Jobs;

use App\Models\EmailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class SendTicketEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300]; // coba ulang 1 menit, lalu 5 menit

    public function __construct(public int $emailLogId, public Mailable $mailable) {}

    public function handle(): void
    {
        $log = EmailLog::findOrFail($this->emailLogId);

        Mail::to($log->to_email)->send($this->mailable);

        $log->update(['status' => 'sent', 'sent_at' => now(), 'error_message' => null]);
    }

    public function failed(Throwable $e): void
    {
        EmailLog::whereKey($this->emailLogId)->update([
            'status' => 'failed',
            'error_message' => Str::limit($e->getMessage(), 1000),
        ]);
    }
}
