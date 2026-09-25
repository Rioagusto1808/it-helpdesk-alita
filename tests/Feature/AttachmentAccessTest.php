<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AuthorType;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use App\Support\TrackingUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentAccessTest extends TestCase
{
    use RefreshDatabase;

    private Ticket $ticket;

    private TicketAttachment $attachment;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::disk('local')->put('attachments/2026/09/acak.pdf', 'isi rahasia');
        $this->ticket = Ticket::factory()->create();
        $this->attachment = $this->ticket->attachments()->create([
            'uploaded_by' => AuthorType::Requester,
            'original_name' => 'surat-kuasa.pdf',
            'stored_path' => 'attachments/2026/09/acak.pdf',
            'mime' => 'application/pdf',
            'size' => 11,
        ]);
    }

    public function test_guest_cannot_download_from_admin_route(): void
    {
        $this->get(route('admin.attachments.show', $this->attachment))->assertRedirect(route('admin.login'));
    }

    public function test_requester_route_requires_valid_signature(): void
    {
        $this->get("/lacak/{$this->ticket->ticket_no}/lampiran/{$this->attachment->id}")->assertForbidden();

        $this->get(TrackingUrl::make($this->ticket, 'tracking.attachment', ['attachment' => $this->attachment->id]))
            ->assertOk()
            ->assertDownload('surat-kuasa.pdf');
    }

    public function test_agent_can_download_as_attachment(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('admin.attachments.show', $this->attachment));

        $response->assertOk()->assertDownload('surat-kuasa.pdf');
        $this->assertStringStartsWith('attachment;', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_inactive_user_cannot_download(): void
    {
        $this->actingAs(User::factory()->inactive()->create())
            ->get(route('admin.attachments.show', $this->attachment))
            ->assertRedirect(route('admin.login'));
    }

    public function test_attachment_of_deleted_ticket_is_not_found(): void
    {
        $this->ticket->delete();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.attachments.show', $this->attachment))
            ->assertNotFound();
    }

    public function test_files_are_not_publicly_reachable(): void
    {
        $this->get('/storage/attachments/2026/09/acak.pdf')->assertNotFound();
    }
}
