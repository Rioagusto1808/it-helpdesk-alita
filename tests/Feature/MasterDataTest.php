<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\HelpdeskSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HelpdeskSeeder::class);
        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    public function test_admin_can_add_and_rename_service_and_it_is_logged(): void
    {
        $this->post(route('admin.services.store'), ['name' => 'VPN', 'sort_order' => 5])->assertRedirect(route('admin.services.index'));

        $vpn = Service::query()->where('name', 'VPN')->sole();
        $this->assertTrue($vpn->is_active);
        $this->assertSame('ITINFRA', $vpn->category()->value('code'));

        $this->put(route('admin.services.update', $vpn), ['name' => 'VPN kantor', 'sort_order' => 6])->assertSessionHasNoErrors();
        $this->assertSame('VPN kantor', $vpn->fresh()?->name);
        $this->assertDatabaseHas('activity_logs', ['action' => 'master.service_saved', 'actor_id' => $this->admin->id]);

        $this->post(route('admin.modules.store'), ['name' => 'Absensi'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('activity_logs', ['action' => 'master.module_saved']);
    }

    public function test_duplicate_name_is_rejected(): void
    {
        $this->post(route('admin.services.store'), ['name' => 'Laptop'])->assertSessionHasErrors('name');
    }

    public function test_used_item_cannot_be_deleted_only_deactivated(): void
    {
        $laptop = Service::query()->where('name', 'Laptop')->sole();
        Ticket::factory()->create(['service_id' => $laptop->id]);

        // Tidak ada route hapus sama sekali.
        $this->delete('/admin/layanan/'.$laptop->id)->assertMethodNotAllowed();
        $this->assertDatabaseHas('services', ['id' => $laptop->id]);

        $this->patch(route('admin.services.toggle', $laptop))->assertSessionHasNoErrors();
        $this->assertFalse($laptop->fresh()?->is_active);
        $this->assertDatabaseHas('tickets', ['service_id' => $laptop->id]);
    }

    public function test_others_cannot_be_deactivated_or_edited(): void
    {
        $others = Service::query()->where('is_other', true)->sole();
        $othersModule = Module::query()->where('is_other', true)->sole();

        $this->patch(route('admin.services.toggle', $others))->assertSessionHasErrors('name');
        $this->patch(route('admin.modules.toggle', $othersModule))->assertSessionHasErrors('name');
        $this->put(route('admin.services.update', $others), ['name' => 'Lain-lain'])->assertSessionHasErrors('name');

        $this->assertTrue($others->fresh()?->is_active);
        $this->assertSame('Others', $others->fresh()?->name);
    }

    public function test_inactive_item_disappears_from_ticket_form(): void
    {
        $printer = Service::query()->where('name', 'Printer')->sole();
        $this->patch(route('admin.services.toggle', $printer));

        $this->get(route('tickets.create'))->assertDontSee('>Printer</option>', false)->assertSee('>Laptop</option>', false);
        $this->get(route('admin.services.index'))->assertSee('Printer')->assertSee('Nonaktif');
    }
}
