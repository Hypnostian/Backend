<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Módulo 2: Catálogo y Gestión de Eventos (RF 2.1 a RF 2.3, RNF 2.1).
 */
class EventoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    private function datosValidos(array $cambios = []): array
    {
        return array_merge([
            'titulo' => 'Laravel Conf Colombia 2026',
            'descripcion' => 'Agenda actualizada.',
            'cupo_maximo' => 350,
        ], $cambios);
    }

    private function tokenDe(User $usuario): string
    {
        return $usuario->createToken('pruebas')->plainTextToken;
    }

    public function test_cualquier_cliente_puede_listar_los_eventos_sin_autenticarse(): void
    {
        $organizador = User::factory()->organizador()->create(['name' => 'Laura Méndez']);
        Evento::factory()->for($organizador, 'organizador')->create(['titulo' => 'Laravel Conf']);

        $this->getJson('/api/eventos')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonStructure([
                ['id', 'user_id', 'titulo', 'descripcion', 'cupo_maximo', 'organizador' => ['id', 'name']],
            ])
            ->assertJsonPath('0.titulo', 'Laravel Conf')
            ->assertJsonPath('0.organizador.name', 'Laura Méndez');
    }

    public function test_el_listado_no_expone_datos_privados_del_organizador(): void
    {
        Evento::factory()->create();

        $this->getJson('/api/eventos')
            ->assertOk()
            ->assertJsonMissingPath('0.organizador.email')
            ->assertJsonMissingPath('0.organizador.role');
    }

    public function test_el_organizador_propietario_puede_actualizar_su_evento(): void
    {
        $evento = Evento::factory()->create();

        $this->withToken($this->tokenDe($evento->organizador))
            ->putJson("/api/eventos/{$evento->id}", $this->datosValidos())
            ->assertOk()
            ->assertJsonPath('message', 'Evento actualizado con éxito')
            ->assertJsonPath('evento.titulo', 'Laravel Conf Colombia 2026')
            ->assertJsonPath('evento.cupo_maximo', 350);

        $this->assertDatabaseHas('eventos', [
            'id' => $evento->id,
            'titulo' => 'Laravel Conf Colombia 2026',
            'descripcion' => 'Agenda actualizada.',
            'cupo_maximo' => 350,
        ]);
    }

    public function test_otro_organizador_no_puede_modificar_un_evento_ajeno(): void
    {
        $evento = Evento::factory()->create(['titulo' => 'Título original']);
        $otroOrganizador = User::factory()->organizador()->create();

        $this->withToken($this->tokenDe($otroOrganizador))
            ->putJson("/api/eventos/{$evento->id}", $this->datosValidos())
            ->assertForbidden()
            ->assertJsonPath('message', 'Solo el organizador propietario puede modificar este evento.');

        $this->assertDatabaseHas('eventos', ['id' => $evento->id, 'titulo' => 'Título original']);
    }

    public function test_ni_el_administrador_puede_modificar_un_evento_que_no_creo(): void
    {
        $evento = Evento::factory()->create(['titulo' => 'Título original']);
        $admin = User::factory()->admin()->create();

        $this->withToken($this->tokenDe($admin))
            ->putJson("/api/eventos/{$evento->id}", $this->datosValidos())
            ->assertForbidden();

        $this->assertDatabaseHas('eventos', ['id' => $evento->id, 'titulo' => 'Título original']);
    }

    public function test_la_propiedad_se_verifica_antes_que_los_datos(): void
    {
        $evento = Evento::factory()->create();
        $otroOrganizador = User::factory()->organizador()->create();

        // Datos inválidos de alguien que no es el dueño: se rechaza por permisos (403), no por validación (422)
        $this->withToken($this->tokenDe($otroOrganizador))
            ->putJson("/api/eventos/{$evento->id}", [])
            ->assertForbidden();
    }

    public function test_actualizar_un_evento_sin_token_responde_401(): void
    {
        $evento = Evento::factory()->create(['titulo' => 'Título original']);

        $this->putJson("/api/eventos/{$evento->id}", $this->datosValidos())
            ->assertUnauthorized();

        $this->assertDatabaseHas('eventos', ['id' => $evento->id, 'titulo' => 'Título original']);
    }

    public function test_actualizar_un_evento_inexistente_responde_404(): void
    {
        $organizador = User::factory()->organizador()->create();

        $this->withToken($this->tokenDe($organizador))
            ->putJson('/api/eventos/999', $this->datosValidos())
            ->assertNotFound()
            ->assertJsonPath('message', 'Recurso no encontrado.');
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function datosInvalidos(): array
    {
        return [
            'título ausente' => [['titulo' => null], 'titulo'],
            'título de más de 100 caracteres' => [['titulo' => str_repeat('a', 101)], 'titulo'],
            'título no textual' => [['titulo' => ['Laravel']], 'titulo'],
            'descripción no textual' => [['descripcion' => ['Agenda']], 'descripcion'],
            'cupo ausente' => [['cupo_maximo' => null], 'cupo_maximo'],
            'cupo en cero' => [['cupo_maximo' => 0], 'cupo_maximo'],
            'cupo negativo' => [['cupo_maximo' => -10], 'cupo_maximo'],
            'cupo decimal' => [['cupo_maximo' => 10.5], 'cupo_maximo'],
            'cupo no numérico' => [['cupo_maximo' => 'cien'], 'cupo_maximo'],
        ];
    }

    /**
     * @param  array<string, mixed>  $cambios
     */
    #[DataProvider('datosInvalidos')]
    public function test_los_datos_que_no_cumplen_las_restricciones_responden_422(array $cambios, string $campo): void
    {
        $evento = Evento::factory()->create(['titulo' => 'Título original']);

        $this->withToken($this->tokenDe($evento->organizador))
            ->putJson("/api/eventos/{$evento->id}", $this->datosValidos($cambios))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$campo]);

        $this->assertDatabaseHas('eventos', ['id' => $evento->id, 'titulo' => 'Título original']);
    }

    public function test_los_mensajes_de_validacion_estan_en_espanol(): void
    {
        $evento = Evento::factory()->create();

        $this->withToken($this->tokenDe($evento->organizador))
            ->putJson("/api/eventos/{$evento->id}", $this->datosValidos(['cupo_maximo' => 0]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.cupo_maximo.0', 'El campo cupo máximo debe ser al menos 1.');
    }

    public function test_el_titulo_admite_hasta_100_caracteres_y_la_descripcion_es_opcional(): void
    {
        $evento = Evento::factory()->create();
        $titulo = str_repeat('a', 100);

        $this->withToken($this->tokenDe($evento->organizador))
            ->putJson("/api/eventos/{$evento->id}", [
                'titulo' => $titulo,
                'descripcion' => null,
                'cupo_maximo' => 1,
            ])
            ->assertOk();

        $this->assertDatabaseHas('eventos', [
            'id' => $evento->id,
            'titulo' => $titulo,
            'descripcion' => null,
            'cupo_maximo' => 1,
        ]);
    }
}
