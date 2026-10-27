<?php

namespace App\Enums;

/**
 * Valores permitidos para la columna citas.estado (por defecto "pendiente").
 */
enum EstadoCita: string
{
    case Pendiente = 'pendiente';
    case Confirmada = 'confirmada';
    case Cancelada = 'cancelada';

    public function etiqueta(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Título del filtro en el listado: "Pendientes", "Confirmadas", "Canceladas".
     */
    public function plural(): string
    {
        return $this->etiqueta().'s';
    }
}
