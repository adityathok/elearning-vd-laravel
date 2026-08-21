<?php

use App\Http\Controllers\Dashboard\UserController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::get('dashboard/users', [UserController::class, 'index'])->name('dashboard.users.index');
    Route::post('dashboard/users', [UserController::class, 'store'])->name('dashboard.users.store');
    Route::patch('dashboard/users/{user}', [UserController::class, 'update'])->name('dashboard.users.update');
});

require __DIR__.'/settings.php';
