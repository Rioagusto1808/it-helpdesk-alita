<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Module;
use App\Models\Service;
use App\Models\Ticket;
use Database\Seeders\HelpdeskSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HelpdeskSeeder::class);
        Queue::fake();
        Storage::fake('local');
    }

    public function test_form_page_lists_categories_and_active_services(): void
    {
        Service::query()->where('name', 'Printer')->update(['is_active' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('ITApps')
            ->assertSee('ITInfra')
            ->assertSee('Laptop')
            ->assertDontSee('Printer</option>', false);
    }

    public function test_itinfra_ticket_is_stored_and_module_fields_are_cleared(): void
    {
        $laptop = $this->service('Laptop');

        $this->post('/tiket', $this->payload([
            'category_id' => $this->category(Category::ITINFRA)->id,
            'service_id' => $laptop->id,
            'module_id' => $this->othersModule()->id,
            'module_other' => 'Harus dibuang',
            'requester_email' => 'Budi@Alita.ID',
        ]))->assertRedirect(route('tickets.submitted'));

        $ticket = Ticket::query()->sole();
        $this->assertSame($laptop->id, $ticket->service_id);
        $this->assertNull($ticket->module_id);
        $this->assertNull($ticket->module_other);
        $this->assertSame('budi@alita.id', $ticket->requester_email);
        $this->assertMatchesRegularExpression('/^IT-\d{8}-\d{5}$/', (string) $ticket->ticket_no);
        $this->assertDatabaseHas('activity_logs', ['action' => 'ticket.created', 'actor_label' => 'Pemohon', 'subject_id' => $ticket->id]);
    }

    public function test_itapps_ticket_is_stored_and_service_fields_are_cleared(): void
    {
        $this->post('/tiket', $this->payload([
            'category_id' => $this->category(Category::ITAPPS)->id,
            'module_id' => $this->othersModule()->id,
            'module_other' => 'Modul absensi',
            'service_id' => $this->service('Laptop')->id,
        ]))->assertRedirect(route('tickets.submitted'));

        $ticket = Ticket::query()->sole();
        $this->assertNull($ticket->service_id);
        $this->assertSame('Modul absensi', $ticket->module_other);
    }

    public function test_others_requires_free_text(): void
    {
        $this->post('/tiket', $this->payload([
            'category_id' => $this->category(Category::ITINFRA)->id,
            'service_id' => $this->service('Others')->id,
        ]))->assertSessionHasErrors('service_other');

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_branch_selection_is_required_for_chosen_category(): void
    {
        $this->post('/tiket', $this->payload(['category_id' => $this->category(Category::ITAPPS)->id]))
            ->assertSessionHasErrors('module_id');
    }

    public function test_inactive_service_is_rejected(): void
    {
        $printer = $this->service('Printer');
        $printer->update(['is_active' => false]);

        $this->post('/tiket', $this->payload(['service_id' => $printer->id]))->assertSessionHasErrors('service_id');
    }

    public function test_attachment_is_stored_on_private_disk(): void
    {
        $this->post('/tiket', $this->payload([
            'attachment' => UploadedFile::fake()->image('layar-error.png'),
        ]))->assertRedirect(route('tickets.submitted'));

        $attachment = Ticket::query()->sole()->attachments()->sole();
        $this->assertSame('layar-error.png', $attachment->original_name);
        $this->assertStringStartsWith('attachments/', $attachment->stored_path);
        $this->assertStringNotContainsString('layar-error', $attachment->stored_path);
        Storage::disk('local')->assertExists($attachment->stored_path);
    }

    public function test_executable_attachment_is_rejected(): void
    {
        $this->post('/tiket', $this->payload([
            'attachment' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'),
        ]))->assertSessionHasErrors('attachment');

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_attachment_larger_than_limit_is_rejected(): void
    {
        $this->post('/tiket', $this->payload([
            'attachment' => UploadedFile::fake()->create('besar.pdf', 5121, 'application/pdf'),
        ]))->assertSessionHasErrors('attachment');
    }

    public function test_filled_honeypot_is_rejected(): void
    {
        $this->post('/tiket', $this->payload(['website' => 'https://spam.example']))->assertSessionHasErrors('website');

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_email_domain_is_restricted_when_configured(): void
    {
        config(['helpdesk.allowed_email_domains' => ['alita.id']]);

        $this->post('/tiket', $this->payload(['requester_email' => 'budi@gmail.com']))->assertSessionHasErrors('requester_email');
        $this->post('/tiket', $this->payload(['requester_email' => 'budi@alita.id']))->assertSessionHasNoErrors();
    }

    public function test_sixth_submission_in_a_minute_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/tiket', $this->payload())->assertRedirect(route('tickets.submitted'));
        }

        $this->post('/tiket', $this->payload())->assertTooManyRequests();
    }

    public function test_success_page_shows_ticket_only_via_session(): void
    {
        $this->get('/tiket/terkirim')->assertRedirect(route('tickets.create'));

        $this->followingRedirects()
            ->post('/tiket', $this->payload())
            ->assertOk()
            ->assertSee(Ticket::query()->sole()->ticket_no)
            ->assertSee('Posisi antrian');
    }

    /** @param array<string, mixed> $overrides */
    private function payload(array $overrides = []): array
    {
        return [
            'category_id' => $this->category(Category::ITINFRA)->id,
            'service_id' => $this->service('Laptop')->id,
            'requester_name' => 'Budi Santoso',
            'requester_email' => 'budi@alita.id',
            'description' => 'Laptop tidak mau menyala sejak pagi.',
            ...$overrides,
        ];
    }

    private function category(string $code): Category
    {
        return Category::query()->where('code', $code)->sole();
    }

    private function service(string $name): Service
    {
        return Service::query()->where('name', $name)->sole();
    }

    private function othersModule(): Module
    {
        return Module::query()->where('is_other', true)->sole();
    }
}
