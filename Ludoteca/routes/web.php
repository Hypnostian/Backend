<?php

use App\Http\Controllers\ExpansionController;
use App\Http\Controllers\IdiomaController;
use App\Http\Controllers\JuegoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmailController;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::resource('idiomas', IdiomaController::class);
Route::get('/juegos/buscar', [JuegoController::class, 'search'])->name('juegos.search');
Route::resource('juegos', JuegoController::class);
Route::resource('expansiones', ExpansionController::class);




Route::get('/', function () {
    return view('welcome');
});

Route::get('/email', [EmailController::class, 'index'])->name('email.index');
Route::post('/email', [EmailController::class, 'send'])->name('email.send');