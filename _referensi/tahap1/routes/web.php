<?php

use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TicketController::class, 'create'])->name('tickets.create');

Route::post('/tickets', [TicketController::class, 'store'])
    ->middleware('throttle:5,1') // maks 5 tiket per menit per IP
    ->name('tickets.store');

Route::get('/tickets/terkirim', [TicketController::class, 'submitted'])->name('tickets.submitted');

// Tahap 2: /queue (papan antrian), /track/{ticket_no} (signed URL), /admin/*
