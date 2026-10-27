# MediCitas · Taller Laravel: Pruebas y Depuración

Aplicación web en Laravel para la gestión de citas médicas de la clínica **MediCitas**. Tiene un módulo web con vistas Blade (listado de citas y formulario de agendamiento), los endpoints JSON equivalentes en `routes/api.php` y una suite de pruebas automatizadas HTTP/API que certifica:

- el filtrado de citas por estado,
- la persistencia en base de datos y el envío del correo al médico,
- la detección y prevención del problema **N+1** en las consultas SQL.

Las pruebas corren sobre **SQLite en memoria**, aisladas de la base MySQL de la aplicación.

## Creadores

| Nombre | GitHub |
| --- | --- |
| Sebastian Lizcano | [@Hypnostian](https://github.com/Hypnostian) |

## Tecnologías

- Laravel 13 · PHP 8.5
- Laravel Sanctum 4 (tokens para la API)
- MySQL 8.4 (aplicación) y SQLite en memoria (pruebas)
- PHPUnit 12
- Mailpit (bandeja de correo local para ver los correos enviados a los médicos)
- Docker con Laravel Sail · Blade + CSS propio (sin compilación con npm)

## Requisitos funcionales

### RF 1.1 · Modelo de datos

- **User:** el modelo nativo de Laravel representa a pacientes y médicos. La migración `2026_10_27_194002_add_rol_y_especialidad_to_users_table.php` agrega `rol` (`paciente` o `medico`) y `especialidad` (solo para médicos).
- **Cita** (`database/migrations/2026_10_27_194014_create_citas_table.php`):

| Columna | Tipo | Detalle |
| --- | --- | --- |
| id | bigint autoincremental | clave primaria |
| patient_id | bigint | clave foránea a `users` (paciente) |
| doctor_id | bigint | clave foránea a `users` (médico asignado) |
| especialidad | varchar | p. ej. "Cardiología", "Pediatría" |
| fecha_hora | datetime | fecha y hora de la cita |
| estado | varchar, por defecto `pendiente` | valores permitidos: `pendiente`, `confirmada`, `cancelada` (enum `App\Enums\EstadoCita`) |
| motivo | text | descripción de la consulta |
| created_at / updated_at | timestamp | |

Relaciones: `Cita::paciente()` y `Cita::medico()` (`belongsTo` a `User`), `User::citasComoPaciente()` y `User::citasComoMedico()` (`hasMany`). Las especialidades están en el enum `App\Enums\Especialidad`.

### RF 2 · Módulo frontend (Blade)

- **`GET /citas`:** listado de las citas agendadas, ordenadas por fecha, con filtros por estado (Todas, Pendientes, Confirmadas, Canceladas) y la cantidad de cada uno. Muestra paciente, médico, especialidad, motivo y estado; las citas del usuario que inició sesión se marcan con "Tú".
- **`GET /citas/agendar`:** formulario de agendamiento para el paciente que inició sesión: especialidad, médico (filtrado según la especialidad), fecha y hora, y motivo. Muestra los errores de validación junto a cada campo.
- **`POST /citas`:** guarda la cita en estado `pendiente`, envía el correo al médico y vuelve al listado con un mensaje de confirmación.
- **`GET /login`:** inicio de sesión. Solo los pacientes pueden agendar; un médico que entra a `/citas/agendar` recibe 403.

### RF 3 · Rutas API (`routes/api.php`)

| Método | URI | Equivale a | Respuesta |
| --- | --- | --- | --- |
| GET | `/api/citas?estado=confirmada` | listado web y sus filtros | 200 `{"data": [...]}` · 422 si el estado no existe |
| POST | `/api/citas` | formulario de agendamiento (`auth:sanctum`) | 201 con la cita · 401 sin sesión · 403 si no es paciente · 422 datos inválidos |
| GET | `/api/medicos?especialidad=Pediatría` | lista de médicos del formulario | 200 |
| POST | `/api/login` | inicio de sesión (entrega un token de Sanctum) | 200 · 401 |
| POST | `/api/logout` | cerrar sesión (revoca el token) | 200 |
| GET | `/api/user` | usuario del token | 200 · 401 |

Ejemplo de una cita en JSON:

```json
{
  "id": 26,
  "especialidad": "Pediatría",
  "fecha_hora": "2026-11-12T08:00:00-05:00",
  "estado": "pendiente",
  "motivo": "Control de crecimiento y vacunas del primer año.",
  "paciente": {"id": 7, "nombre": "Juan Sebastian Lizcano Jaimes"},
  "medico": {"id": 4, "nombre": "Dra. Camila Torres", "especialidad": "Pediatría"},
  "creada_en": "2026-10-27T14:57:54-05:00"
}
```

### Validación al agendar (web y API)

`App\Http\Requests\AgendarCitaRequest` se usa en los dos módulos:

| Campo | Reglas |
| --- | --- |
| especialidad | obligatoria, una de las especialidades de la clínica |
| doctor_id | obligatorio, debe ser un **médico** que atienda la especialidad elegida |
| fecha_hora | obligatoria, fecha válida y **futura**; el médico no puede tener otra cita activa (no cancelada) a esa misma hora |
| motivo | obligatorio, entre 10 y 1000 caracteres |

El paciente es siempre el usuario autenticado y el estado siempre empieza en `pendiente`, aunque la petición envíe otros valores. Los mensajes están en español (`lang/es`).

## Requisitos técnicos de pruebas y depuración

### RT 1 · Base de datos en memoria

`phpunit.xml` configura la conexión de las pruebas:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="DB_URL" value=""/>
```

Cada prueba usa `RefreshDatabase`: las migraciones se ejecutan sobre una base SQLite que vive en memoria y se descarta al terminar, así que las pruebas son rápidas, aisladas y nunca tocan los datos de MySQL. La prueba `ModeloDatosTest::test_las_pruebas_corren_sobre_sqlite_en_memoria` verifica el driver (`sqlite`) y la base (`:memory:`).

### RT 2 · Suite de pruebas (`tests/Feature/CitaApiTest.php`)

| Prueba | Qué verifica |
| --- | --- |
| `test_muestra_citas_confirmadas` | Crea 3 citas confirmadas, 2 pendientes y 1 cancelada; `GET /api/citas?estado=confirmada` responde 200 con exactamente las 3 confirmadas y ninguna de las otras. |
| `test_agendar_cita_y_envio_correo` | Con `Mail::fake()`, un paciente autenticado (`actingAs`) agenda con `POST /api/citas`: responde 201 con estado `pendiente`, la cita queda en la base de datos (`assertDatabaseHas`) y se envía **un** `NuevaCitaAgendada` dirigido al correo del médico. |
| `test_detecta_problema_n_mas_1` | Crea 10 citas, cada una con su propio paciente y su propio médico, cuenta las consultas SQL con `DB::listen` durante `GET /api/citas` y exige **máximo 3**: una para las citas, una para los pacientes y una para los médicos. |

Las pruebas imprimen evidencia con `dump()`: la respuesta JSON de la cita creada y el total y la lista de consultas SQL ejecutadas.

## Depuración del problema N+1

### 1. Diagnóstico

El listado de citas empezó **sin carga anticipada** (marcado como intencional para la depuración), igual que el ejemplo de clase:

```php
// app/Services/AgendaService.php (versión inicial)
return Cita::query()
    ->when($estado, fn ($query) => $query->where('estado', $estado))
    ->orderBy('fecha_hora')
    ->get();
```

`CitaResource` lee `$this->paciente->name` y `$this->medico->name`, así que cada cita disparaba dos consultas más. La prueba lo detectó:

```text
"Total de consultas SQL ejecutadas: 21" // tests/Feature/CitaApiTest.php:105
array:21 [
  0 => "select * from "citas" order by "fecha_hora" asc"
  1 => "select * from "users" where "users"."id" = ? limit 1"
  2 => "select * from "users" where "users"."id" = ? limit 1"
  ...
  20 => "select * from "users" where "users"."id" = ? limit 1"
]

   FAIL  Tests\Feature\CitaApiTest
  ✓ muestra citas confirmadas
  ✓ agendar cita y envio correo
  ⨯ detecta problema n mas 1

  Se detectó un problema de N+1. Consultas ejecutadas: 21
Failed asserting that 21 is equal to 3 or is less than 3.
```

21 consultas = 1 (citas) + 10 (un paciente por cita) + 10 (un médico por cita). Con 100 citas serían 201.

### 2. Corrección

Se agregó *eager loading* con `with()`:

```php
return Cita::query()
    ->with(['paciente', 'medico'])
    ->when($estado, fn ($query) => $query->where('estado', $estado))
    ->orderBy('fecha_hora')
    ->get();
```

Resultado de la misma prueba:

```text
"Total de consultas SQL ejecutadas: 3" // tests/Feature/CitaApiTest.php:105
array:3 [
  0 => "select * from "citas" order by "fecha_hora" asc"
  1 => "select * from "users" where "users"."id" in (1, 3, 5, 7, 9, 11, 13, 15, 17, 19)"
  2 => "select * from "users" where "users"."id" in (2, 4, 6, 8, 10, 12, 14, 16, 18, 20)"
]

   PASS  Tests\Feature\CitaApiTest
  ✓ muestra citas confirmadas
  ✓ agendar cita y envio correo
  ✓ detecta problema n mas 1

  Tests:    3 passed (20 assertions)
```

Ahora son 3 consultas sin importar cuántas citas haya. El listado web usa el mismo servicio (`AgendaService::citas()`), así que también quedó corregido: `CitaWebTest::test_listado_web_no_tiene_problema_n_mas_1` lo comprueba (3 consultas + 1 para los totales de los filtros).

### 3. Prevención

- `AppServiceProvider` activa `Model::preventLazyLoading()` en el entorno local: si alguien vuelve a quitar el `with()`, la página falla de inmediato en desarrollo en lugar de hacer cientos de consultas en silencio. Se comprobó quitando el `with()` temporalmente: `GET /api/citas` respondió `LazyLoadingViolationException: Attempted to lazy load [paciente] on model [App\Models\Cita] but lazy loading is disabled.`
- `ConsultaApiTest::test_los_listados_no_cargan_relaciones_de_forma_diferida` activa la misma protección y recorre la API y la vista.
- La prueba `test_detecta_problema_n_mas_1` queda en la suite como prueba de regresión.

### Otras herramientas de depuración usadas

- `dump()` en las pruebas para ver la respuesta JSON y las consultas SQL.
- `DB::listen()` para registrar cada consulta con su SQL.
- `Mail::fake()` y `Mail::assertSent()` para revisar el correo sin enviarlo; en desarrollo los correos llegan a **Mailpit** (http://localhost:8026).
- `Exceptions::fake()` para comprobar que, si el servidor de correo falla, el error se reporta y la cita igual queda guardada.
- `./vendor/bin/sail artisan pail` para seguir los logs de la aplicación en vivo.

## Arquitectura

| Clase | Responsabilidad |
| --- | --- |
| `App\Services\AgendaService` | Consultar las citas (con eager loading), contar por estado y agendar: guarda la cita y envía el correo al médico. La usan el módulo web y la API. |
| `App\Http\Controllers\CitaController` | Vistas Blade: listado, formulario y guardado |
| `App\Http\Controllers\Api\CitaController` | `GET` y `POST /api/citas` |
| `App\Http\Controllers\Api\MedicoController` | `GET /api/medicos` |
| `App\Http\Controllers\Api\AuthController` / `LoginController` | Tokens de Sanctum / sesión web |
| `App\Http\Requests\AgendarCitaRequest` | Reglas de validación compartidas |
| `App\Http\Resources\CitaResource` | Formato JSON de una cita |
| `App\Policies\CitaPolicy` | Solo los pacientes pueden agendar (`->can('create', Cita::class)` en las rutas) |
| `App\Mail\NuevaCitaAgendada` | Correo al médico (`resources/views/emails/nueva-cita*.blade.php`, HTML y texto) |

Si el envío del correo falla, la cita no se pierde: el error se registra con `report()` y la respuesta sigue siendo 201.

## Ejecución con Docker (Laravel Sail)

Requiere Docker Desktop. Los comandos se ejecutan desde **Ubuntu (WSL)** dentro de `PruebasyDepuracion/medicitas`. Los contenedores son `medicitas-laravel.test-1`, `medicitas-mysql-1` y `medicitas-mailpit-1`.

| Servicio | Dirección |
| --- | --- |
| Aplicación | http://localhost:8083 |
| API | http://localhost:8083/api |
| Mailpit (correos enviados) | http://localhost:8026 |
| MySQL | `localhost:3310` · base `medicitas` · usuario `sail` · contraseña `password` |

Los puertos son distintos a los de los otros talleres, así que pueden estar encendidos al mismo tiempo.

### Primera vez (después de clonar el repositorio)

```bash
cp .env.example .env
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php84-composer:latest composer install --ignore-platform-reqs
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
```

### Migraciones y semillas de prueba (seeders)

```bash
./vendor/bin/sail artisan migrate                # crear las tablas
./vendor/bin/sail artisan db:seed                # cargar los datos de prueba
./vendor/bin/sail artisan migrate:fresh --seed   # borrar todo y empezar de cero con los datos de prueba
```

`DatabaseSeeder` llama a:

| Seeder | Datos |
| --- | --- |
| `MedicoSeeder` | 6 médicos: Dra. Laura Méndez y Dr. Mateo Herrera (Medicina general), Dr. Andrés Rojas (Cardiología), Dra. Camila Torres (Pediatría), Dr. Felipe Ortiz (Dermatología) y Dra. Valentina Ríos (Ginecología) |
| `PacienteSeeder` | 8 pacientes: Juan Sebastian Lizcano Jaimes y 7 generados con Faker |
| `CitaSeeder` | 24 citas futuras entre esos pacientes y médicos: 12 confirmadas, 6 pendientes y 6 canceladas |

Cuentas para probar (contraseña `password` en todas):

| Rol | Correo |
| --- | --- |
| Paciente | `jlizcano471@unab.edu.co` |
| Médico | `andres.rojas@medicitas.test` (y los demás `nombre.apellido@medicitas.test`) |

### Pruebas automatizadas

```bash
./vendor/bin/sail artisan test                       # toda la suite
./vendor/bin/sail artisan test --filter=CitaApiTest  # solo las 3 pruebas del taller
```

### Uso diario

```bash
./vendor/bin/sail up -d       # encender (app, MySQL y Mailpit)
./vendor/bin/sail artisan pail  # ver los logs en vivo
./vendor/bin/sail stop        # apagar
```

Desde PowerShell, en la misma carpeta:

```powershell
docker compose up -d
docker compose exec -u sail laravel.test php artisan test
docker compose stop
```

### Paso a paso con el que se construyó

| Paso | Comando |
| --- | --- |
| Proyecto Laravel 13 | `composer create-project laravel/laravel medicitas` (imagen `laravelsail/php84-composer`) |
| Sail con MySQL y Mailpit | `composer require laravel/sail --dev` y `php artisan sail:install --with=mysql,mailpit` |
| API con Sanctum | `php artisan install:api` |
| Enums | `./vendor/bin/sail artisan make:enum EstadoCita --string` (y `Rol`, `Especialidad`) |
| Columnas de rol en users | `./vendor/bin/sail artisan make:migration add_rol_y_especialidad_to_users_table --table=users` |
| Modelo, migración y factory | `./vendor/bin/sail artisan make:model Cita -m -f` |
| Correo al médico | `./vendor/bin/sail artisan make:mail NuevaCitaAgendada` |
| Validación, recurso JSON y política | `make:request AgendarCitaRequest`, `make:resource CitaResource`, `make:policy CitaPolicy --model=Cita` |
| Servicio | `./vendor/bin/sail artisan make:class Services/AgendaService` |
| Controladores | `make:controller CitaController`, `Api/CitaController`, `Api/AuthController`, `Api/MedicoController --invokable`, `LoginController` |
| Seeders | `make:seeder MedicoSeeder`, `PacienteSeeder`, `CitaSeeder` |
| Pruebas | `./vendor/bin/sail artisan make:test CitaApiTest` (y las demás clases de `tests/Feature`) |
| Migrar y sembrar | `./vendor/bin/sail artisan migrate --seed` |
| Ejecutar las pruebas | `./vendor/bin/sail artisan test --filter=CitaApiTest` |

## Pruebas automatizadas

45 pruebas (261 aserciones) sobre SQLite en memoria; ninguna envía correos reales.

| Clase | Cubre |
| --- | --- |
| `CitaApiTest` | Las 3 pruebas del taller (RT 2) |
| `AgendarCitaValidacionTest` | 401 sin sesión, 403 para médicos, campos obligatorios, fecha futura, especialidad del médico, horario ocupado y liberado por una cancelación, estado inicial forzado a `pendiente`, falla del correo |
| `ConsultaApiTest` | Listado sin filtro y ordenado, filtros pendiente/cancelada, estado inválido (422), sin carga diferida de relaciones, `GET /api/medicos` |
| `CitaWebTest` | Listado y filtros en Blade, N+1 en la vista, redirección al login, formulario, agendar desde la web con correo, errores de validación, 403 para médicos y contenido del correo |
| `AutenticacionTest` | Login y logout web, token de Sanctum para agendar, credenciales incorrectas y revocación del token |
| `ModeloDatosTest` | SQLite en memoria (RT 1), columnas de `citas`, estado por defecto, claves foráneas, relaciones, tipos y seeders |
| `Unit\EstadoCitaTest` | Solo existen los estados `pendiente`, `confirmada` y `cancelada` |

## Pruebas manuales (27/10/2026, a nombre de Juan Sebastian Lizcano Jaimes)

| # | Prueba | Resultado |
| --- | --- | --- |
| 1 | Inicio de sesión como paciente desde `/citas/agendar` | Redirige al login y, al ingresar, vuelve al formulario. |
| 2 | Agendar desde la web: Cardiología, Dr. Andrés Rojas, 5/11/2026 10:30 a. m. | La cita aparece en el listado como **Pendiente** con la etiqueta "Tú" y Mailpit recibe "Nueva cita agendada: 5 de noviembre, 10:30 a. m." dirigido a andres.rojas@medicitas.test. |
| 3 | API: `POST /api/login` y `POST /api/citas` con el token (Pediatría, Dra. Camila Torres) | 201 con la cita en JSON; el mismo horario otra vez responde 422 "El médico ya tiene una cita agendada en esa fecha y hora." |
| 4 | API: datos inválidos, sin token, como médico y con el token revocado | 422 con los mensajes en español, 401 "No has iniciado sesión.", 403 "Solo los pacientes pueden agendar citas." y 401. |
| 5 | Web como médico | El listado marca sus citas con "Tú", no muestra "Agendar cita" y `/citas/agendar` responde 403. |
| 6 | Quitar el `with()` en local | `LazyLoadingViolationException` inmediata (prevención del N+1). |
| 7 | Ejecutar toda la suite y revisar MySQL | 45 pruebas aprobadas; MySQL conserva sus 26 citas: las pruebas no tocan la base de la aplicación. |
