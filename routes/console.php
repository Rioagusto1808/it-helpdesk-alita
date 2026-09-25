<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// Butuh cron tiap menit: * * * * * php /path/artisan schedule:run (lihat README).
Schedule::command('helpdesk:auto-close')->dailyAt('01:00')->withoutOverlapping()->onOneServer();
