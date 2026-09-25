<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Tickets\AutoCloseResolvedTickets;
use Illuminate\Console\Command;

/** Dijadwalkan harian pukul 01.00 di routes/console.php. */
final class AutoCloseTicketsCommand extends Command
{
    protected $signature = 'helpdesk:auto-close';

    protected $description = 'Tutup tiket selesai yang tidak dibalas pemohon selama HELPDESK_AUTO_CLOSE_DAYS hari.';

    public function handle(AutoCloseResolvedTickets $autoClose): int
    {
        $this->info("{$autoClose->handle()} tiket ditutup otomatis.");

        return self::SUCCESS;
    }
}
