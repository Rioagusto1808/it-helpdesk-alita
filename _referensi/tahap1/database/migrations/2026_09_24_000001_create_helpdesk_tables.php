<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admin & agent IT login lewat tabel users bawaan Laravel.
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('agent')->after('email'); // admin | agent
            $table->boolean('is_active')->default(true)->after('role');
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // ITPASS | ITINFRA
            $table->string('name', 50);
            $table->timestamps();
        });

        // Layanan untuk ITInfra: Laptop, Printer, Internet, Email, Others
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->boolean('is_other')->default(false); // true = munculkan input teks
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['category_id', 'name']);
        });

        // Modul untuk ITPass
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->boolean('is_other')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_no', 30)->nullable()->unique(); // diisi otomatis oleh TicketObserver
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
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'id']); // untuk hitung posisi antrian
        });

        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->string('original_name', 255);
            $table->string('stored_path', 255);
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            $table->timestamps();
        });

        Schema::create('ticket_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_type', 20); // requester | agent | system
            $table->text('body');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();
        });

        Schema::create('ticket_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 60)->index();
            $table->nullableMorphs('subject');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->json('properties')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('to_email', 150);
            $table->string('subject', 255);
            $table->string('type', 40);
            $table->string('status', 10)->default('queued'); // queued | sent | failed
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('ticket_status_histories');
        Schema::dropIfExists('ticket_comments');
        Schema::dropIfExists('ticket_attachments');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('services');
        Schema::dropIfExists('categories');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active']);
        });
    }
};
