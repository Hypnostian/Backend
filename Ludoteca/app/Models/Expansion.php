<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expansion extends Model
{
    protected $table = 'expansiones';
    protected $fillable = ['juego_id', 'titulo', 'idioma_id'];

    public function juego(): BelongsTo
    {
        return $this->belongsTo(Juego::class, 'juego_id');
    }

    public function idioma(): BelongsTo
    {
        return $this->belongsTo(Idioma::class, 'idioma_id');
    }
}

