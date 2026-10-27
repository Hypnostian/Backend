<?php

namespace App\Enums;

/**
 * Especialidades que atiende la clínica. El valor es el texto que se guarda en la base de datos.
 */
enum Especialidad: string
{
    case MedicinaGeneral = 'Medicina general';
    case Cardiologia = 'Cardiología';
    case Pediatria = 'Pediatría';
    case Dermatologia = 'Dermatología';
    case Ginecologia = 'Ginecología';
}
