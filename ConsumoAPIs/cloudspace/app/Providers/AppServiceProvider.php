<?php

namespace App\Providers;

use App\Services\StripeService;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Cliente del SDK oficial con la llave secreta de config/services.php (nunca escrita en el código)
        $this->app->bind(StripeService::class, fn () => new StripeService(
            new StripeClient(['api_key' => config('services.stripe.secret') ?: null]),
            config('services.stripe.webhook_secret') ?: null,
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
