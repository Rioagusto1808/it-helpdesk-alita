<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Ticket;
use DateTimeInterface;
use Illuminate\Support\Facades\URL;

/** Signed URL untuk semua halaman dan aksi tracking pemohon. */
final class TrackingUrl
{
    /**
     * Tanpa $expires: link baru berlaku HELPDESK_TRACKING_LINK_DAYS hari (untuk email).
     * Dengan $expires: ikut masa berlaku link yang sedang dibuka (untuk form di halaman tracking).
     *
     * @param  array<string, mixed>  $params
     */
    public static function make(Ticket $ticket, string $route = 'tracking.show', array $params = [], ?DateTimeInterface $expires = null): string
    {
        // Tanda tangan relatif (path + query saja): link tetap valid di balik proxy HTTPS atau saat domain berubah.
        // Divalidasi middleware "signed:relative" di routes/web.php.
        return url(URL::temporarySignedRoute(
            $route,
            $expires ?? now()->addDays(config('helpdesk.tracking_link_days')),
            ['ticket' => $ticket->ticket_no, ...$params],
            absolute: false,
        ));
    }
}
