<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class Juego
{
    protected $table = 'juegos';
    public $timestamps = true;
    protected $fillable = ['titulo', 'anio', 'idioma_id'];

    public static function all()
    {
        return DB::table('juegos as j')
            ->join('idiomas as i', 'i.id', '=', 'j.idioma_id')
            ->select('j.id', 'j.titulo', 'j.anio', 'i.nombre as idioma')
            ->orderBy('j.titulo')
            ->get();
    }

    public static function find($id)
    {
        return DB::table('juegos as j')
            ->join('idiomas as i', 'i.id', '=', 'j.idioma_id')
            ->select('j.id', 'j.titulo', 'j.anio', 'j.idioma_id', 'i.nombre as idioma')
            ->where('j.id', $id)
            ->first();
    }

    public static function create(array $datos)
    {
        return DB::table('juegos')->insertGetId([
            'titulo' => $datos['titulo'] ?? null,
            'anio' => $datos['anio'] ?? null,
            'idioma_id' => $datos['idioma_id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function update($id, array $datos)
    {
        return DB::table('juegos')->where('id', $id)->update([
            'titulo' => $datos['titulo'] ?? null,
            'anio' => $datos['anio'] ?? null,
            'idioma_id' => $datos['idioma_id'] ?? null,
            'updated_at' => now(),
        ]);
    }

    public static function destroy($id)
    {
        return DB::table('juegos')->where('id', $id)->delete();
    }
}
