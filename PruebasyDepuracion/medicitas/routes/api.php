<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CitaController;
use App\Http\Controllers\Api\MedicoController;
use App\Models\Cita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('api.login');

Route::get('/citas', [CitaController::class, 'index'])->name('api.citas.index');
Route::get('/medicos', MedicoController::class)->name('api.medicos.index');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/citas', [CitaController::class, 'store'])
        ->can('create', Cita::class)
        ->name('api.citas.store');

    Route::get('/user', function (Request $request) {
        return $request->user();
    })->name('api.user');

    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
});
