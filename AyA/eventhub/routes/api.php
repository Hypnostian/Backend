<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Rutas Públicas
Route::post('/login', [AuthController::class, 'login']);
Route::get('/eventos', [EventoController::class, 'index']);

// Rutas Protegidas por Autenticación
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/perfil', [UserController::class, 'perfil']);
    Route::put('/eventos/{evento}', [EventoController::class, 'update']);
    Route::get('/admin/metricas', [DashboardController::class, 'metricas']);
});
