<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/** Notifikasi reset bawaan Laravel: dikirim lewat queue dan menunjuk ke halaman reset panel admin. */
final class ResetPasswordNotification extends ResetPassword implements ShouldQueue
{
    use Queueable;

    /** @param mixed $notifiable */
    protected function resetUrl($notifiable): string
    {
        return route('admin.password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);
    }
}
