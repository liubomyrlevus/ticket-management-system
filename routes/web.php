<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Профіль доступний всім залогіненим
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // --- БЛОК ТІКЕТІВ ---

    // 1. Доступ для всіх (Клієнти можуть переглядати та створювати)
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::put('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
    Route::post('/tickets/{ticket}/comments', [\App\Http\Controllers\CommentController::class, 'store'])->name('comments.store');

    // 2. Доступ ТІЛЬКИ для Адмінів та Стаффу (редагування та видалення)
    Route::middleware('role:admin,staff')->group(function () {
        Route::get('/tickets/{ticket}/edit', [TicketController::class, 'edit'])->name('tickets.edit');
        Route::patch('/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.updateStatus');
        Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy');
    });

    // 2. Маршрути, доступні ТІЛЬКИ для Адмінів (управління користувачами)
    Route::middleware(['auth', 'role:admin'])->group(function () {
        Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users');
        Route::patch('/admin/users/{user}', [AdminController::class, 'updateUser'])->name('admin.update');
        Route::delete('/admin/users/{user}', [AdminController::class, 'destroy'])->name('admin.destroy'); // Додай це!
    });
});

require __DIR__.'/auth.php';
