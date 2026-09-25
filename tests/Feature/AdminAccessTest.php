<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\EmailLog;
use App\Models\Module;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_gets_403_on_admin_only_pages_and_actions(): void
    {
        $agent = User::factory()->create();
        $service = Service::factory()->create();
        $module = Module::factory()->create();
        $ticket = Ticket::factory()->create();
        $other = User::factory()->create();
        $log = EmailLog::query()->create(['ticket_id' => $ticket->id, 'to_email' => 'a@alita.id', 'subject' => 'x', 'type' => 'admin_new_ticket', 'status' => 'failed']);

        $this->actingAs($agent);

        $requests = [
            ['get', route('admin.services.index')], ['post', route('admin.services.store')], ['patch', route('admin.services.toggle', $service)],
            ['get', route('admin.modules.index')], ['put', route('admin.modules.update', $module)],
            ['get', route('admin.users.index')], ['post', route('admin.users.store')], ['patch', route('admin.users.toggle', $other)],
            ['get', route('admin.logs.activity')], ['get', route('admin.logs.email')], ['post', route('admin.logs.email.retry', $log)],
            ['get', route('admin.tickets.export')], ['delete', route('admin.tickets.destroy', $ticket)],
            ['patch', route('admin.tickets.assign', $ticket)],
        ];

        foreach ($requests as [$method, $url]) {
            $this->{$method}($url, ['name' => 'x'])->assertForbidden();
        }

        $this->assertTrue($service->fresh()?->is_active);
        $this->assertTrue($other->fresh()?->is_active);
        $this->assertNotSoftDeleted($ticket);
    }

    public function test_agent_can_open_shared_pages(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['admin.dashboard', 'admin.dashboard.data', 'admin.tickets.index', 'admin.profile.password'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_admin_can_open_every_admin_page(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        foreach (['admin.dashboard', 'admin.tickets.index', 'admin.services.index', 'admin.modules.index', 'admin.users.index', 'admin.logs.activity', 'admin.logs.email'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }
}
