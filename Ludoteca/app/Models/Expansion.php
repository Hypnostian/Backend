<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class Expansion
{
    protected $table = 'expansiones';
    public $timestamps = true;
    protected $fillable = ['juego_id', 'titulo', 'idioma_id'];

    public static function all()
    {
        return DB::table('expansiones as e')
            ->join('juegos as j', 'j.id', '=', 'e.juego_id')
            ->join('idiomas as i', 'i.id', '=', 'e.idioma_id')
            ->select('e.id', 'e.titulo', 'j.titulo as juego_base', 'i.nombre as idioma', 'e.juego_id', 'e.idioma_id')
            ->orderBy('e.titulo', 'asc')
            ->get();
    }

    public static function find($id)
    {
        return DB::table('expansiones as e')
            ->join('juegos as j', 'j.id', '=', 'e.juego_id')
            ->join('idiomas as i', 'i.id', '=', 'e.idioma_id')
            ->select('e.id', 'e.titulo', 'e.juego_id', 'e.idioma_id', 'j.titulo as juego_base', 'i.nombre as idioma')
            ->where('e.id', $id)
            ->first();
    }

    public static function create(array $datos)
    {
        return DB::table('expansiones')->insertGetId([
            'juego_id' => $datos['juego_id'] ?? null,
            'titulo' => $datos['titulo'] ?? null,
            'idioma_id' => $datos['idioma_id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function update($id, array $datos)
    {
        return DB::table('expansiones')->where('id', $id)->update([
            'juego_id' => $datos['juego_id'] ?? null,
            'titulo' => $datos['titulo'] ?? null,
            'idioma_id' => $datos['idioma_id'] ?? null,
            'updated_at' => now(),
        ]);
    }

    public static function destroy($id)
    {
        return DB::table('expansiones')->where('id', $id)->delete();
    }
}

