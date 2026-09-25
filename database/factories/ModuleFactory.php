<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Module>
 */
class ModuleFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'is_other' => false,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 50),
        ];
    }

    public function other(): static
    {
        return $this->state(['name' => 'Others', 'is_other' => true, 'sort_order' => 999]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
