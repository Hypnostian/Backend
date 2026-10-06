<?php

use App\Http\Controllers\ContactoController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PlatoController;
use Illuminate\Support\Facades\Route;

Route::get('/', InicioController::class)->name('inicio');

// Módulos 1 y 2: registro, gestión y mantenimiento de platos (administración).
Route::get('/platos', [PlatoController::class, 'index'])->name('platos.index');
Route::get('/platos/crear', [PlatoController::class, 'create'])->name('platos.create');
Route::post('/platos', [PlatoController::class, 'store'])->name('platos.store');
Route::get('/platos/{plato}/editar', [PlatoController::class, 'edit'])->name('platos.edit');
Route::put('/platos/{plato}', [PlatoController::class, 'update'])->name('platos.update');
Route::delete('/platos/{plato}', [PlatoController::class, 'destroy'])->name('platos.destroy');

// Módulo 3: carta del restaurante (vista pública).
Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
Route::get('/menu/{plato}', [MenuController::class, 'show'])->name('menu.show');

// Formulario de contacto.
Route::get('/contacto', [ContactoController::class, 'create'])->name('contacto.create');
Route::post('/contacto', [ContactoController::class, 'send'])->name('contacto.send');
