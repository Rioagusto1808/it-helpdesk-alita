<?php

declare(strict_types=1);

namespace App\Actions\Emails;

use App\Enums\EmailStatus;
use App\Models\ActivityLog;
use App\Models\EmailLog;
use App\Models\User;
use App\Support\TicketNotifier;
use Illuminate\Validation\ValidationException;

final class RetryEmail
{
    public function __construct(private readonly TicketNotifier $notifier) {}

    public function handle(EmailLog $log, User $actor): void
    {
        if ($log->status !== EmailStatus::Failed) {
            throw ValidationException::withMessages(['email' => 'Hanya email yang gagal yang bisa dikirim ulang.']);
        }

        $this->notifier->retry($log);
        ActivityLog::record('email.retried', $log, ['to' => $log->to_email, 'type' => $log->type], $actor);
    }
}
