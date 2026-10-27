<?php

namespace App\Http\Resources;

use App\Models\Cita;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Cita
 */
class CitaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'especialidad' => $this->especialidad,
            'fecha_hora' => $this->fecha_hora->toIso8601String(),
            'estado' => $this->estado,
            'motivo' => $this->motivo,
            // Si la consulta no cargó las relaciones con with(), cada acceso dispara una consulta SQL (N+1)
            'paciente' => [
                'id' => $this->paciente->id,
                'nombre' => $this->paciente->name,
            ],
            'medico' => [
                'id' => $this->medico->id,
                'nombre' => $this->medico->name,
                'especialidad' => $this->medico->especialidad,
            ],
            'creada_en' => $this->created_at?->toIso8601String(),
        ];
    }
}
