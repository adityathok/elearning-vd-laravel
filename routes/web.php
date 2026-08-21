<?php

use App\Http\Controllers\Dashboard\GeneralSettingController;
use App\Http\Controllers\Dashboard\UserController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::get('dashboard/users', [UserController::class, 'index'])->name('dashboard.users.index');
    Route::post('dashboard/users', [UserController::class, 'store'])->name('dashboard.users.store');
    Route::patch('dashboard/users/{user}', [UserController::class, 'update'])->name('dashboard.users.update');
    Route::delete('dashboard/users/{user}', [UserController::class, 'destroy'])->name('dashboard.users.destroy');
    Route::get('dashboard/pengaturan/umum', [GeneralSettingController::class, 'edit'])->name('dashboard.pengaturan.umum.edit');
    Route::patch('dashboard/pengaturan/umum', [GeneralSettingController::class, 'update'])->name('dashboard.pengaturan.umum.update');
});

require __DIR__.'/settings.php';
