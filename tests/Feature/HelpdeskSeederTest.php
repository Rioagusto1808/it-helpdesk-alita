<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Module;
use App\Models\Service;
use App\Models\Ticket;
use Database\Seeders\HelpdeskSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpdeskSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_itpass_category_is_renamed_to_itapps_keeping_tickets(): void
    {
        $legacy = Category::query()->create(['code' => 'ITPASS', 'name' => 'ITPass']);
        $ticket = Ticket::factory()->itApps()->create(['category_id' => $legacy->id]);
        $migration = require database_path('migrations/2026_09_25_000001_rename_itpass_category_to_itapps.php');

        $migration->up();

        $this->assertSame(['ITAPPS', 'ITApps'], [$legacy->fresh()?->code, $legacy->fresh()?->name]);
        $this->assertSame($legacy->id, $ticket->fresh()?->category_id);

        $migration->down();
        $this->assertSame('ITPASS', $legacy->fresh()?->code);
    }

    public function test_seeder_is_idempotent_and_creates_master_data(): void
    {
        $this->seed(HelpdeskSeeder::class);
        $this->seed(HelpdeskSeeder::class);

        $this->assertSame(2, Category::query()->count());
        $this->assertSame(
            ['Laptop', 'Printer', 'Internet', 'Email', 'Others'],
            Service::query()->active()->pluck('name')->all(),
        );
        $this->assertDatabaseHas('modules', ['name' => 'Others', 'is_other' => true, 'sort_order' => 999]);
        $this->assertSame(1, Module::query()->count());
    }
}
