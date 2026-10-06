<?php

namespace App\Models;

use Database\Factories\PlatoFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plato extends Model
{
    /** @use HasFactory<PlatoFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'platos';

    /**
     * Atributos que se pueden asignar masivamente.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'precio',
        'descripcion',
        'imagen',
    ];

    /**
     * Conversiones de tipos de los atributos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
        ];
    }

    /**
     * URL pública de la imagen, servida a través del enlace public/storage.
     */
    protected function imagenUrl(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->imagen ? asset('storage/'.$this->imagen) : null
        );
    }

    /**
     * Precio con formato de moneda colombiana, p. ej. "$ 28.500" o "$ 28.500,50".
     */
    protected function precioFormateado(): Attribute
    {
        return Attribute::get(function (): string {
            $precio = (float) $this->precio;
            $decimales = floor($precio) == $precio ? 0 : 2;

            return '$ '.number_format($precio, $decimales, ',', '.');
        });
    }
}
