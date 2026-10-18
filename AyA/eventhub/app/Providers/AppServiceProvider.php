<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Solo el Administrador Global puede ver el recaudo y las métricas de la plataforma
        Gate::define('ver-metricas', function (User $user) {
            return $user->role === 'admin'
                ? Response::allow()
                : Response::deny('Solo un administrador puede consultar las métricas de la plataforma.');
        });
    }
}
