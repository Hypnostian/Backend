<?php

namespace App\Models;

use Database\Factories\EventoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'titulo', 'descripcion', 'cupo_maximo'])]
class Evento extends Model
{
    /** @use HasFactory<EventoFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'cupo_maximo' => 'integer',
        ];
    }

    /**
     * Organizador (usuario) que creó el evento.
     */
    public function organizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
