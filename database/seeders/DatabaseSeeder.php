<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Module;
use App\Models\Service;
use App\Models\Ticket;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    private const SAMPLE_TICKETS = 30;

    public function run(): void
    {
        $this->call(HelpdeskSeeder::class);

        if (! app()->isLocal() || Ticket::query()->exists()) {
            return;
        }

        $services = Service::query()->where('is_other', false)->get();
        $othersModule = Module::query()->where('is_other', true)->firstOrFail();

        for ($i = 0; $i < self::SAMPLE_TICKETS; $i++) {
            $factory = Ticket::factory()
                ->status(fake()->randomElement(TicketStatus::cases()))
                ->priority(fake()->randomElement(TicketPriority::cases()));

            $factory = $i % 3 === 0
                ? $factory->itApps()->state(['module_id' => $othersModule->id, 'module_other' => fake()->words(2, true)])
                : $factory->state(['service_id' => $services->random()->id]);

            $factory->create(['created_at' => now()->subMinutes((self::SAMPLE_TICKETS - $i) * 47)]);
        }
    }
}
