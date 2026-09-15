<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Idioma extends Model
{
    protected $table = 'idiomas';
    protected $fillable = ['nombre', 'codigo'];

    public function juegos(): HasMany
    {
        return $this->hasMany(Juego::class, 'idioma_id');
    }

    public function expansiones(): HasMany
    {
        return $this->hasMany(Expansion::class, 'idioma_id');
    }
}


