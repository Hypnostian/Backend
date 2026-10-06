# El Fogón · Taller Laravel: Formularios

Módulo en Laravel para administrar los platos del menú de un restaurante. Aplica validación con **Form Requests**, almacenamiento seguro de imágenes en el servidor, persistencia en **MySQL** con **Eloquent** y vistas con **Blade**.

## Creadores

| Nombre | GitHub |
| --- | --- |
| Sebastian Lizcano | [@Hypnostian](https://github.com/Hypnostian) |
| Simon Andres Jaimes Moncayo | [@moncayosimon35-coconut](https://github.com/moncayosimon35-coconut) |

## Tecnologías

- Laravel 12 · PHP 8.5
- MySQL 8.4
- Blade + CSS propio (sin compilación con npm)
- Docker con Laravel Sail

## Funcionalidades

### Módulo 1: Registro de platos (`/platos/crear`)

- Formulario con `enctype="multipart/form-data"`, `@csrf`, valores conservados con `old()` y errores con `@error`.
- Validación con `App\Http\Requests\PlatoRequest`:

  | Campo | Reglas |
  | --- | --- |
  | nombre | obligatorio, texto, 3 a 100 caracteres, único en la tabla `platos` |
  | precio | obligatorio, numérico, mínimo 1000.00 |
  | descripcion | obligatoria, texto, mínimo 10 caracteres |
  | imagen | obligatoria, imagen JPG, PNG o WEBP de máximo 2 MB |

- El controlador usa `$request->validated()`, guarda la imagen, crea el registro con Eloquent y redirige al formulario con un mensaje de éxito.

### Módulo 2: Gestión y mantenimiento (`/platos`)

- Listado en tabla con ID, miniatura, nombre, precio y las acciones **Editar** y **Eliminar**, más el botón destacado **Registrar plato**.
- Edición (`/platos/{id}/editar`) con `POST`, `@csrf`, `@method('PUT')` y vista previa de la imagen actual.
- `App\Http\Requests\UpdatePlatoRequest` hereda las reglas del registro con dos excepciones: el nombre sigue siendo único pero ignora el propio plato, y la imagen es opcional (si no se envía, se conserva la anterior).
- Si se sube una imagen nueva, se guarda en la carpeta `AAAA/MM` del momento y la antigua se elimina físicamente del servidor.
- Eliminación con formulario `POST` + `@method('DELETE')` + `@csrf`, confirmación en JavaScript y **soft delete** en la base de datos.

### Módulo 3: Carta del restaurante (vista pública)

- `/menu`: platos en orden alfabético (agrupados por letra) con nombre, precio en formato de moneda (`$ 28.500`) y enlace **Ver detalle**.
- `/menu/{id}`: nombre, imagen, descripción, precio y botón **Volver al menú**.

### Extras

- Página de inicio y formulario de contacto (envía correo si `MAIL_CONTACT_TO` está configurado; si no, muestra el resumen de lo enviado).
- Mensajes de validación en español, páginas 404 y 419 personalizadas y diseño adaptable a celulares.
- Seeder con 9 platos de ejemplo y sus ilustraciones.
- 38 pruebas automatizadas.

## Almacenamiento de imágenes

Las imágenes se guardan en el disco `public` con el nombre del plato:

```
storage/app/public/platos/AAAA/MM/nombre-del-plato.ext
storage/app/public/platos/2026/09/hamburguesa-artesanal.png
```

En la base de datos solo se guarda la ruta relativa (`platos/2026/09/hamburguesa-artesanal.png`). Si ya existe un archivo con ese nombre en la carpeta, se agrega un sufijo (`-2`, `-3`, ...). El acceso público se configura con el enlace simbólico `public/storage → storage/app/public`:

```bash
./vendor/bin/sail artisan storage:link
```

## Base de datos

Tabla `platos` (migración `database/migrations/2026_10_06_140914_create_platos_table.php`):

| Columna | Tipo | Detalle |
| --- | --- | --- |
| id | bigint autoincremental | clave primaria |
| nombre | varchar(100) | con índice |
| precio | decimal(10, 2) | número de punto flotante con 2 decimales fijos, ideal para dinero |
| descripcion | text | |
| imagen | varchar(255) | ruta relativa de la imagen |
| created_at / updated_at | timestamp | |
| deleted_at | timestamp | para el soft delete |

## Rutas

| Método | URI | Acción |
| --- | --- | --- |
| GET | `/` | Inicio |
| GET | `/platos` | Listado de administración |
| GET | `/platos/crear` | Formulario de registro |
| POST | `/platos` | Guardar plato |
| GET | `/platos/{plato}/editar` | Formulario de edición |
| PUT | `/platos/{plato}` | Actualizar plato |
| DELETE | `/platos/{plato}` | Eliminar plato (soft delete) |
| GET | `/menu` | Carta pública |
| GET | `/menu/{plato}` | Detalle del plato |
| GET / POST | `/contacto` | Formulario de contacto |

## Ejecución con Docker (Laravel Sail)

Requiere Docker Desktop. El proyecto se puede manejar desde **Ubuntu (WSL)** o desde **PowerShell**; ambos usan los mismos contenedores (`formularios-laravel.test-1` y `formularios-mysql-1`).

| Servicio | Dirección |
| --- | --- |
| Aplicación | http://localhost:8080 |
| MySQL | `localhost:3307` · base `formularios` · usuario `sail` · contraseña `password` |

Los puertos son distintos a los de otros proyectos con Sail, así que pueden estar encendidos al mismo tiempo.

### Primera vez (después de clonar el repositorio)

Desde Ubuntu (WSL), dentro de la carpeta del proyecto:

```bash
cp .env.example .env
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php84-composer:latest composer install --ignore-platform-reqs
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail artisan storage:link
```

### Uso diario

```bash
./vendor/bin/sail up -d       # encender
./vendor/bin/sail stop        # apagar
./vendor/bin/sail ps          # ver estado
./vendor/bin/sail logs -f     # ver registros
./vendor/bin/sail artisan test
```

Desde PowerShell, en la misma carpeta:

```powershell
docker compose up -d
docker compose exec laravel.test php artisan migrate
docker compose stop
```

### Rendimiento

El archivo `docker/php/opcache.ini` activa OPcache para el servidor de desarrollo. Como el proyecto está en una carpeta de Windows montada en Docker, cada página tarda unos segundos. Si se necesita más velocidad, se puede mover el proyecto al sistema de archivos de WSL (por ejemplo `~/proyectos`).

## Pruebas

```bash
./vendor/bin/sail artisan test
```

Cubren el registro con la ruta `AAAA/MM`, todas las reglas de validación, el nombre único (también al editar), la conservación y el reemplazo de la imagen, el soft delete, el orden alfabético y el formato de moneda de la carta, y el detalle de cada plato.
