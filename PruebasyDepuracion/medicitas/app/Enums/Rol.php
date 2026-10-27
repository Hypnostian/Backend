<?php

namespace App\Enums;

/**
 * Los pacientes y los médicos son usuarios del modelo nativo User; el rol los distingue.
 */
enum Rol: string
{
    case Paciente = 'paciente';
    case Medico = 'medico';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Paciente => 'Paciente',
            self::Medico => 'Médico',
        };
    }
}
