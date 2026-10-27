<?php

use App\Http\Controllers\CitaController;
use App\Http\Controllers\LoginController;
use App\Models\Cita;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/citas');

Route::get('/citas', [CitaController::class, 'index'])->name('citas.index');

Route::middleware('auth')->group(function () {
    Route::get('/citas/agendar', [CitaController::class, 'create'])
        ->can('create', Cita::class)
        ->name('citas.create');

    Route::post('/citas', [CitaController::class, 'store'])
        ->can('create', Cita::class)
        ->name('citas.store');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');
});
