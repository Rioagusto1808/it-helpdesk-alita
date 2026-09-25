<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            // Nullable sebentar: diisi TicketObserver setelah ID ada.
            $table->string('ticket_no', 30)->nullable()->unique();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('service_other', 100)->nullable();
            $table->foreignId('module_id')->nullable()->constrained()->nullOnDelete();
            $table->string('module_other', 100)->nullable();
            $table->string('requester_name', 100);
            $table->string('requester_email', 150)->index();
            $table->text('description');
            $table->string('status', 20)->default('baru');
            $table->string('priority', 10)->default('sedang');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'id']);
            $table->index(['assigned_to', 'status']);
            $table->index(['category_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
