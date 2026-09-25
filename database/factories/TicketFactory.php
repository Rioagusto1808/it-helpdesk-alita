<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Module;
use App\Models\Service;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Default: tiket ITInfra dengan layanan biasa, status baru, prioritas sedang.
 *
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'category_id' => fn () => Category::query()->firstOrCreate(['code' => Category::ITINFRA], ['name' => 'ITInfra'])->id,
            'service_id' => Service::factory(),
            'module_id' => null,
            'requester_name' => fake()->name(),
            'requester_email' => fake()->unique()->safeEmail(),
            'description' => fake()->paragraph(),
            'status' => TicketStatus::Baru,
            'priority' => TicketPriority::Sedang,
            'ip_address' => fake()->ipv4(),
        ];
    }

    public function itApps(): static
    {
        return $this->state([
            'category_id' => fn () => Category::query()->firstOrCreate(['code' => Category::ITAPPS], ['name' => 'ITApps'])->id,
            'service_id' => null,
            'module_id' => Module::factory(),
        ]);
    }

    public function status(TicketStatus $status): static
    {
        return $this->state(['status' => $status]);
    }

    public function priority(TicketPriority $priority): static
    {
        return $this->state(['priority' => $priority]);
    }
}
