<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
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
        // Prevención del problema N+1 durante el desarrollo: si una vista o un recurso lee una relación
        // que no se cargó con with(), Laravel lanza LazyLoadingViolationException en lugar de hacer
        // una consulta por cada registro. En las pruebas el N+1 se diagnostica contando las consultas.
        Model::preventLazyLoading($this->app->isLocal());
    }
}
