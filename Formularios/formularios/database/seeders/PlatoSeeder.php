<?php

namespace Database\Seeders;

use App\Models\Plato;
use Illuminate\Database\Seeder;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PlatoSeeder extends Seeder
{
    /**
     * Carga platos de ejemplo con sus ilustraciones (database/seeders/imagenes).
     */
    public function run(): void
    {
        $platos = [
            [
                'nombre' => 'Tiramisú',
                'precio' => 15900,
                'descripcion' => "Clásico postre italiano con capas de bizcocho empapado en café espresso, crema de mascarpone y cacao amargo espolvoreado.\nSe prepara cada mañana en nuestra cocina.",
            ],
            [
                'nombre' => 'Limonada de Coco',
                'precio' => 9800,
                'descripcion' => 'Limón natural licuado con crema de coco y hielo, servida bien fría. Refrescante y cremosa, perfecta para acompañar cualquier plato de la carta.',
            ],
            [
                'nombre' => 'Arepa con Queso',
                'precio' => 12500,
                'descripcion' => 'Arepas de maíz blanco asadas a la parrilla, rellenas de queso campesino derretido y terminadas con mantequilla de la casa. Se acompañan con hogao casero.',
            ],
            [
                'nombre' => 'Ajiaco Santafereño',
                'precio' => 32000,
                'descripcion' => "Sopa tradicional bogotana con tres tipos de papa, pollo desmechado, mazorca tierna y guascas.\nSe sirve con crema de leche, alcaparras, arroz blanco y aguacate.",
            ],
            [
                'nombre' => 'Ensalada César',
                'precio' => 24000,
                'descripcion' => 'Lechuga romana crujiente, pollo a la plancha, crutones de pan artesanal y lascas de queso parmesano, bañados en nuestro aderezo César preparado en casa.',
            ],
            [
                'nombre' => 'Salmón a la Parrilla',
                'precio' => 46500,
                'descripcion' => 'Filete de salmón de 220 g asado a la parrilla con mantequilla de hierbas, servido con espárragos salteados, puré de papa criolla y rodajas de limón.',
            ],
            [
                'nombre' => 'Pizza Margarita',
                'precio' => 36000,
                'descripcion' => 'Masa madre fermentada durante 48 horas, salsa de tomate, mozzarella fresca y albahaca, horneada en nuestro horno de leña.',
            ],
            [
                'nombre' => 'Ceviche de Camarón',
                'precio' => 34900,
                'descripcion' => 'Camarones frescos marinados en limón con cebolla morada, tomate, cilantro y un toque de ají. Acompañado de chips de plátano verde crocantes.',
            ],
            [
                'nombre' => 'Hamburguesa Artesanal',
                'precio' => 28500,
                'descripcion' => "Carne de res madurada de 180 g, queso cheddar fundido, cebolla caramelizada, tocineta crocante y salsa de la casa en pan brioche horneado cada mañana.\nIncluye papas a la francesa y salsa de tomate.",
            ],
        ];

        $carpeta = 'platos/'.now()->format('Y/m');

        foreach ($platos as $datos) {
            // Permite ejecutar el seeder varias veces sin duplicar platos.
            if (Plato::withTrashed()->where('nombre', $datos['nombre'])->exists()) {
                continue;
            }

            $slug = Str::slug($datos['nombre']);
            $imagen = Storage::disk('public')->putFileAs(
                $carpeta,
                new File(database_path("seeders/imagenes/{$slug}.webp")),
                "{$slug}.webp"
            );

            Plato::create([...$datos, 'imagen' => $imagen]);
        }
    }
}
