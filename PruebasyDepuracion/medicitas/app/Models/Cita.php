<?php

namespace App\Models;

use App\Enums\Especialidad;
use App\Enums\EstadoCita;
use Database\Factories\CitaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['patient_id', 'doctor_id', 'especialidad', 'fecha_hora', 'estado', 'motivo'])]
class Cita extends Model
{
    /** @use HasFactory<CitaFactory> */
    use HasFactory;

    /**
     * Igual que el valor por defecto de la columna: así la cita recién creada ya trae su estado.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'estado' => 'pendiente',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'especialidad' => Especialidad::class,
            'fecha_hora' => 'datetime',
            'estado' => EstadoCita::class,
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
}
