<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\Support\InteractuaConStripe;
use Tests\TestCase;

class SeguridadYConfiguracionTest extends TestCase
{
    use InteractuaConStripe, RefreshDatabase;

    public function test_las_credenciales_se_mapean_desde_el_env_en_config_services(): void
    {
        $this->assertSame('pk_test_cloudspace', config('services.stripe.key'));
        $this->assertSame('sk_test_cloudspace', config('services.stripe.secret'));
        $this->assertSame('whsec_cloudspace_pruebas', config('services.stripe.webhook_secret'));
        $this->assertSame('SG.cloudspace-pruebas', config('services.sendgrid.key'));
        $this->assertSame('pagos@cloudspace.test', config('services.sendgrid.from.address'));

        $ejemplo = File::get(base_path('.env.example'));

        foreach (['STRIPE_KEY', 'STRIPE_SECRET', 'STRIPE_WEBHOOK_SECRET', 'SENDGRID_API_KEY', 'SENDGRID_FROM_ADDRESS'] as $variable) {
            $this->assertMatchesRegularExpression("/^{$variable}=$/m", $ejemplo, "{$variable} debe estar vacía en .env.example");
        }
    }

    public function test_ninguna_credencial_esta_escrita_en_el_codigo(): void
    {
        $archivos = collect(File::allFiles(app_path()))
            ->merge(File::allFiles(config_path()))
            ->merge(File::allFiles(resource_path('views')));

        foreach ($archivos as $archivo) {
            $this->assertDoesNotMatchRegularExpression(
                '/(sk|pk|rk)_(test|live)_[A-Za-z0-9]{10,}|whsec_[A-Za-z0-9]{10,}|SG\.[A-Za-z0-9_-]{10,}/',
                $archivo->getContents(),
                "Credencial escrita en {$archivo->getRelativePathname()}",
            );
        }
    }

    public function test_los_controladores_y_modelos_no_hablan_directo_con_las_apis(): void
    {
        $archivos = collect(File::allFiles(app_path('Http/Controllers')))->merge(File::allFiles(app_path('Models')));

        foreach ($archivos as $archivo) {
            $codigo = $archivo->getContents();
            $nombre = $archivo->getRelativePathname();

            $this->assertStringNotContainsString('use Stripe\\', $codigo, "{$nombre} no debe usar el SDK de Stripe");
            $this->assertStringNotContainsString('Facades\\Http', $codigo, "{$nombre} no debe hacer peticiones HTTP");
            $this->assertStringNotContainsString('api.sendgrid.com', $codigo, "{$nombre} no debe llamar a SendGrid");
            $this->assertStringNotContainsString('api.stripe.com', $codigo, "{$nombre} no debe llamar a Stripe");
        }
    }

    public function test_la_pagina_de_checkout_solo_expone_la_llave_publicable(): void
    {
        $this->get('/checkout')
            ->assertOk()
            ->assertSee('pk_test_cloudspace')
            ->assertDontSee('sk_test_cloudspace')
            ->assertDontSee('whsec_cloudspace_pruebas');
    }

    public function test_el_webhook_esta_excluido_de_csrf_y_los_formularios_no(): void
    {
        // Durante las pruebas Laravel omite la verificación CSRF; se simula un entorno normal
        $this->app['env'] = 'local';
        Http::preventStrayRequests();
        $this->simularStripe();

        $cuerpo = $this->eventoStripe('payment_intent.created');

        $this->enviarAlWebhook($cuerpo)->assertOk()->assertJsonPath('received', true);

        $this->post('/email', ['destinatarios' => 'ana@cloudspace.test', 'asunto' => 'Prueba', 'mensaje' => 'Hola'])
            ->assertStatus(419);

        $this->postJson('/create-payment-intent', ['monto' => 50000, 'moneda' => 'cop'])
            ->assertStatus(419);

        Http::assertNothingSent();
        $this->assertSame([], $this->stripeHttp->peticiones);
    }

    public function test_los_formularios_incluyen_el_token_csrf(): void
    {
        $this->get('/email')->assertSee('name="_token"', false);

        $this->get('/checkout')
            ->assertSee('name="_token"', false)
            ->assertSee('<meta name="csrf-token"', false)
            ->assertSee("'X-CSRF-TOKEN': config.csrf", false);
    }
}
