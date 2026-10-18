# EventHub Solutions · Taller Laravel: Autenticación y Autorización

API RESTful en Laravel para la plataforma de gestión, promoción y comercialización de eventos y conferencias tecnológicas de **EventHub Solutions**. Implementa autenticación sin estado con tokens de **Laravel Sanctum**, control de acceso por rol con un **Gate** y autorización sobre el recurso con una **Policy**, respondiendo siempre en JSON.

## Creadores

| Nombre | GitHub |
| --- | --- |
| Sebastian Lizcano | [@Hypnostian](https://github.com/Hypnostian) |


## Tecnologías

- Laravel 13 · PHP 8.5
- Laravel Sanctum 4 (tokens de acceso personales)
- MySQL 8.4
- Docker con Laravel Sail

## Requisitos funcionales

### Módulo 1: Identidad y Autenticación

- **RF 1.1 · `POST /api/login`:** valida `email` y `password`, compara la contraseña con `Hash::check()` y, si es correcta, emite un token con `createToken()` (trait `HasApiTokens` de Sanctum) que se devuelve como `{"token": "...", "token_type": "Bearer"}`. Si el correo no existe o la contraseña es incorrecta responde **401** con el mismo mensaje, para no revelar qué cuentas existen.
- **RF 1.2 · `GET /api/perfil`:** devuelve los datos del dueño del token (`$request->user()`), sin la contraseña.

### Módulo 2: Catálogo y Gestión de Eventos (Policy)

- **RF 2.1 · `GET /api/eventos`:** ruta pública que lista los eventos con el `id` y el `name` del organizador responsable (`Evento::with('organizador:id,name')`), sin exponer su correo ni su rol.
- **RF 2.2 · `PUT /api/eventos/{id}`:** valida cada campo según el modelo de datos:

  | Campo | Reglas |
  | --- | --- |
  | titulo | obligatorio, texto, máximo 100 caracteres |
  | descripcion | opcional (acepta `null`), texto |
  | cupo_maximo | obligatorio, entero, mayor que cero (`min:1`) |

- **RF 2.3 · Verificación de propiedad:** antes de validar o guardar, `Gate::authorize('update', $evento)` ejecuta `EventoPolicy::update()`, que compara de forma estricta `$user->id === $evento->user_id`. Cualquier otro usuario, incluido el administrador, recibe **403** y el evento no cambia.

### Módulo 3: Métricas y Reportes Globales (Gate)

- **RF 3.1 · `GET /api/admin/metricas`:** devuelve el total consolidado de eventos (`Evento::count()`) y el monto global recaudado por la plataforma.
- **RF 3.2 · Restricción administrativa:** el Gate `ver-metricas` solo autoriza a usuarios con rol `admin`; organizadores y asistentes reciben **403**.

> El modelo de datos del taller no registra ventas de boletas, por eso `recaudo_total` es un valor de referencia (48.750.000 COP).

## Requisitos no funcionales

| Requisito | Implementación |
| --- | --- |
| RNF 1.1 · Autenticación sin estado | Laravel Sanctum (`install:api`). El token viaja en la cabecera `Authorization: Bearer ...` y en la base de datos solo se guarda su hash SHA-256 (tabla `personal_access_tokens`); no se usan sesiones ni cookies. |
| RNF 1.2 · Contraseñas | Cast `'password' => 'hashed'` en el modelo `User` y `bcrypt()` en el seeder: se almacenan con hash bcrypt, nunca en texto plano. |
| RNF 1.3 · Protección de rutas | `GET /api/perfil`, `PUT /api/eventos/{id}` y `GET /api/admin/metricas` están dentro de `Route::middleware('auth:sanctum')`. |
| RNF 2.1 · Policy | `App\Policies\EventoPolicy`; el modelo `Evento` no tiene validaciones condicionales y el controlador solo invoca `Gate::authorize()`. |
| RNF 2.2 · Gate | `Gate::define('ver-metricas', ...)` en `App\Providers\AppServiceProvider`; el `DashboardController` solo llama a `Gate::authorize('ver-metricas')`. |
| RNF 3.1 · Respuestas JSON | Todas las rutas `/api/*` responden JSON con **200**, **401**, **403**, **422** (y **404** si el evento no existe), incluso si el cliente no envía `Accept: application/json` (configurado en `bootstrap/app.php`). Los mensajes de validación están en español (`lang/es`). |

## Modelo de datos

Tabla `users` (`database/migrations/2026_10_13_190049_create_users_table.php`):

| Columna | Tipo | Detalle |
| --- | --- | --- |
| id | bigint autoincremental | clave primaria |
| name | varchar(100) | obligatorio |
| email | varchar(150) | obligatorio, único |
| password | varchar(255) | hash bcrypt |
| role | enum('admin', 'organizador', 'asistente') | obligatorio, por defecto `asistente` |
| created_at / updated_at | timestamp | |

Tabla `eventos` (`database/migrations/2026_10_13_190152_create_eventos_table.php`):

| Columna | Tipo | Detalle |
| --- | --- | --- |
| id | bigint autoincremental | clave primaria |
| user_id | bigint | obligatorio, clave foránea a `users` (al borrar el organizador se borran sus eventos) |
| titulo | varchar(100) | obligatorio |
| descripcion | text | opcional |
| cupo_maximo | int unsigned | obligatorio, mayor que cero (validado en la API) |
| created_at / updated_at | timestamp | |

La migración de usuarios se generó con `make:migration create_users_table`, como indica la guía, y reemplaza a la migración nativa de Laravel (`0001_01_01_000000_create_users_table.php`), que se eliminó para no crear dos veces la tabla `users`. Conserva las tablas auxiliares que traía la nativa (`password_reset_tokens` y `sessions`). Las demás tablas son `personal_access_tokens` (Sanctum), `cache` y `jobs`.

## Endpoints

| Método | URI | Acceso | Respuestas |
| --- | --- | --- | --- |
| POST | `/api/login` | público | 200 token · 401 credenciales erróneas · 422 datos inválidos |
| GET | `/api/eventos` | público | 200 |
| GET | `/api/perfil` | token | 200 · 401 |
| PUT | `/api/eventos/{id}` | token del organizador propietario | 200 · 401 · 403 · 404 · 422 |
| GET | `/api/admin/metricas` | token de un administrador | 200 · 401 · 403 |

## Datos semilla

`database/seeders/DatabaseSeeder.php` crea estos usuarios (contraseña de todos: `password`):

| Rol | Nombre | Email |
| --- | --- | --- |
| admin | Admin Global | admin@eventhub.com |
| organizador (Organizador A) | Laura Méndez | laura@eventhub.com |
| organizador (Organizador B) | Carlos Rojas | carlos@eventhub.com |
| asistente | Sofía Torres | sofia@eventhub.com |

Y tres eventos: el **1** y el **2** a nombre del Organizador A (*Laravel Conf Colombia 2026* y *Taller de APIs seguras con Sanctum*) y el **3** a nombre del Organizador B (*Cloud & DevOps Summit Bucaramanga*).

## Ejecución con Docker (Laravel Sail)

Requiere Docker Desktop. Los comandos se ejecutan desde **Ubuntu (WSL)**, dentro de la carpeta del proyecto (`AyA/eventhub`). Los contenedores son `eventhub-laravel.test-1` y `eventhub-mysql-1`.

| Servicio | Dirección |
| --- | --- |
| API | http://localhost:8081/api |
| MySQL | `localhost:3308` · base `eventhub` · usuario `sail` · contraseña `password` |

Los puertos son distintos a los de los otros talleres, así que pueden estar encendidos al mismo tiempo.

### Primera vez (después de clonar el repositorio)

```bash
cp .env.example .env
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php84-composer:latest composer install --ignore-platform-reqs
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
```

### Uso diario

```bash
./vendor/bin/sail up -d                         # encender
./vendor/bin/sail artisan migrate:fresh --seed  # reiniciar la base con los datos semilla
./vendor/bin/sail artisan route:list --path=api # ver los endpoints
./vendor/bin/sail stop                          # apagar
```

Desde PowerShell, en la misma carpeta:

```powershell
docker compose up -d
docker compose exec laravel.test php artisan migrate:fresh --seed
docker compose stop
```

### Paso a paso con el que se construyó

| Paso | Comando |
| --- | --- |
| Soporte de API y Sanctum | `./vendor/bin/sail artisan install:api` |
| Migración de usuarios | `./vendor/bin/sail artisan make:migration create_users_table` |
| Modelo y migración de eventos | `./vendor/bin/sail artisan make:model Evento -m` |
| Migrar | `./vendor/bin/sail artisan migrate` |
| Controlador de autenticación | `./vendor/bin/sail artisan make:controller AuthController` |
| Controlador de perfiles | `./vendor/bin/sail artisan make:controller UserController` |
| Controlador del Gate | `./vendor/bin/sail artisan make:controller DashboardController` |
| Policy de eventos | `./vendor/bin/sail artisan make:policy EventoPolicy --model=Evento` |
| Controlador de eventos | `./vendor/bin/sail artisan make:controller EventoController` |
| Factory para las pruebas | `./vendor/bin/sail artisan make:factory EventoFactory --model=Evento` |
| Datos semilla | `./vendor/bin/sail artisan migrate:fresh --seed` |

## Pruebas de la API

En Postman los datos van en **Body → raw → JSON** y el token en **Authorization → Bearer Token**. Con `curl` desde Ubuntu:

**Prueba 1 · Obtener el token de acceso (autenticación)**

```bash
curl -X POST http://localhost:8081/api/login \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"email": "laura@eventhub.com", "password": "password"}'
```

```json
{"token": "1|nVnncnhZlY8hooTM9lDX3W2yLM492jFPQ8K4OtN35c52f6c9", "token_type": "Bearer"}
```

**Prueba 2 · Ver el perfil (validación del token)**

```bash
curl http://localhost:8081/api/perfil \
  -H "Accept: application/json" -H "Authorization: Bearer <token>"
```

Sin token o con un token inválido responde **401** `{"message": "No autenticado. Envía un token Bearer válido."}`.

**Prueba 3 · Actualizar un evento (autorización por Policy)**

```bash
curl -X PUT http://localhost:8081/api/eventos/1 \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{"titulo": "Laravel Conf Colombia 2026 - Edición Bucaramanga", "descripcion": "Agenda actualizada.", "cupo_maximo": 350}'
```

- Caso A: con el token de `laura@eventhub.com` (propietaria del evento 1) → **200** con el evento actualizado.
- Caso B: con el token de `carlos@eventhub.com` → **403** `{"message": "Solo el organizador propietario puede modificar este evento."}`.
- Con `"cupo_maximo": 0` o un título de más de 100 caracteres → **422** con los errores de cada campo.

**Prueba 4 · Consultar las métricas (autorización por Gate)**

```bash
curl http://localhost:8081/api/admin/metricas \
  -H "Accept: application/json" -H "Authorization: Bearer <token>"
```

- Caso A: con el token de un organizador o del asistente → **403** `{"message": "Solo un administrador puede consultar las métricas de la plataforma."}`.
- Caso B: con el token de `admin@eventhub.com` → **200** `{"total_eventos": 3, "recaudo_total": 48750000, "moneda": "COP"}`.

## Pruebas automatizadas

```bash
./vendor/bin/sail artisan test
```

42 pruebas que usan una base SQLite en memoria (no tocan los datos de MySQL). Cubren el login (200, 401 y 422), el perfil con token, sin token y con token inválido, el listado público con el nombre del organizador, la actualización por el propietario, el rechazo a otros organizadores y al administrador (403, verificado antes que los datos), las reglas de cada campo (422), el Gate de métricas por rol, el hash de las contraseñas, el rol por defecto, el correo único y los datos semilla.
