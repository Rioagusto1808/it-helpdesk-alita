<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmailLogController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\TicketAssignmentController;
use App\Http\Controllers\Admin\TicketCommentController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Admin\TicketResponseController;
use App\Http\Controllers\Admin\TicketStatusController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\QueueBoardController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TicketController::class, 'create'])->name('tickets.create');
Route::post('/tiket', [TicketController::class, 'store'])->middleware('throttle:5,1')->name('tickets.store');
Route::get('/tiket/terkirim', [TicketController::class, 'submitted'])->name('tickets.submitted');

Route::get('/antrian', [QueueBoardController::class, 'index'])->name('queue.index');
Route::get('/antrian/data', [QueueBoardController::class, 'data'])->middleware('throttle:60,1')->name('queue.data');

Route::controller(TrackingController::class)->group(function (): void {
    Route::get('/lacak', 'lookup')->name('tracking.lookup');
    Route::post('/lacak', 'sendLink')->middleware('throttle:5,1')->name('tracking.send-link');

    // Signed URL = bukti kepemilikan tiket bagi pemohon yang tidak login.
    Route::middleware('signed:relative')->scopeBindings()->group(function (): void {
        Route::get('/lacak/{ticket:ticket_no}', 'show')->name('tracking.show');
        Route::post('/lacak/{ticket:ticket_no}/balas', 'reply')->middleware('throttle:tracking-reply')->name('tracking.reply');
        Route::post('/lacak/{ticket:ticket_no}/batal', 'cancel')->name('tracking.cancel');
        Route::get('/lacak/{ticket:ticket_no}/lampiran/{attachment}', 'attachment')->name('tracking.attachment');
    });
});

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [LoginController::class, 'create'])->name('login');
        Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login')->name('login.attempt');
        Route::get('/lupa-password', [PasswordResetController::class, 'request'])->name('password.request');
        Route::post('/lupa-password', [PasswordResetController::class, 'email'])->middleware('throttle:3,1')->name('password.email');
        Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
        Route::post('/reset-password/{token}', [PasswordResetController::class, 'update'])->name('password.update');
    });

    Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

    Route::middleware(['auth', 'active'])->group(function (): void {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/profil/password', [PasswordController::class, 'edit'])->name('profile.password');
        Route::put('/profil/password', [PasswordController::class, 'update'])->name('profile.password.update');

        Route::get('/tiket', [AdminTicketController::class, 'index'])->name('tickets.index');
        Route::get('/tiket/export', [AdminTicketController::class, 'export'])->middleware('role:admin')->name('tickets.export');
        Route::prefix('/tiket/{ticket}')->whereNumber('ticket')->name('tickets.')->group(function (): void {
            Route::get('/', [AdminTicketController::class, 'show'])->name('show');
            Route::delete('/', [AdminTicketController::class, 'destroy'])->middleware('role:admin')->name('destroy');
            Route::patch('/status', [TicketStatusController::class, 'update'])->name('status');
            Route::patch('/prioritas', [TicketStatusController::class, 'priority'])->name('priority');
            Route::post('/ambil', [TicketAssignmentController::class, 'take'])->name('take');
            Route::patch('/assign', [TicketAssignmentController::class, 'assign'])->middleware('role:admin')->name('assign');
            Route::post('/komentar', [TicketCommentController::class, 'store'])->name('comments.store');
            Route::post('/balas', [TicketResponseController::class, 'store'])->name('respond');
            Route::post('/kirim-link', [AdminTicketController::class, 'sendLink'])->name('send-link');
        });
        Route::get('/lampiran/{attachment}', [AttachmentController::class, 'show'])->name('attachments.show');
        Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');

        // Tambah dan ubah di halaman index yang sama; tidak ada show/destroy (data hanya dinonaktifkan).
        Route::middleware('role:admin')->group(function (): void {
            Route::resource('layanan', ServiceController::class)->only(['index', 'store', 'edit', 'update'])
                ->parameters(['layanan' => 'service'])->names('services');
            Route::patch('/layanan/{service}/toggle', [ServiceController::class, 'toggle'])->name('services.toggle');

            Route::resource('modul', ModuleController::class)->only(['index', 'store', 'edit', 'update'])
                ->parameters(['modul' => 'module'])->names('modules');
            Route::patch('/modul/{module}/toggle', [ModuleController::class, 'toggle'])->name('modules.toggle');

            Route::resource('user', UserController::class)->only(['index', 'store', 'edit', 'update'])->names('users');
            Route::patch('/user/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');

            Route::get('/log/aktivitas', [ActivityLogController::class, 'index'])->name('logs.activity');
            Route::get('/log/email', [EmailLogController::class, 'index'])->name('logs.email');
            Route::post('/log/email/{emailLog}/kirim-ulang', [EmailLogController::class, 'retry'])->name('logs.email.retry');
        });
    });
});
