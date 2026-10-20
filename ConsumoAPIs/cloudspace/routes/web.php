<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', InicioController::class)->name('inicio');

// Módulo 1: Procesamiento de pagos (Stripe API)
Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/create-payment-intent', [CheckoutController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('checkout.payment-intent');
Route::get('/checkout/resultado', [CheckoutController::class, 'resultado'])->name('checkout.resultado');

// Módulo 2: Notificaciones asíncronas (Stripe Webhook). Exenta de CSRF en bootstrap/app.php
Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');
Route::get('/pagos', [PagoController::class, 'index'])->name('pagos.index');

// Módulo 3: Correos transaccionales (SendGrid Mail)
Route::get('/email', [EmailController::class, 'create'])->name('email.create');
Route::post('/email', [EmailController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('email.store');

// Logs de las integraciones (eventos y errores de Stripe, el webhook y SendGrid)
Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
