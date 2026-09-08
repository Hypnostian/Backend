<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class Idioma
{
    protected $table = 'idiomas';
    public $timestamps = true;
    protected $fillable = ['nombre', 'codigo'];

    public static function all()
    {
        return DB::table('idiomas')->orderBy('nombre')->get();
    }

    public static function find($id)
    {
        return DB::table('idiomas')->where('id', $id)->first();
    }

    public static function create(array $datos)
    {
        return DB::table('idiomas')->insertGetId([
            'nombre' => $datos['nombre'] ?? null,
            'codigo' => $datos['codigo'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function update($id, array $datos)
    {
        return DB::table('idiomas')->where('id', $id)->update([
            'nombre' => $datos['nombre'] ?? null,
            'codigo' => $datos['codigo'] ?? null,
            'updated_at' => now(),
        ]);
    }

    public static function destroy($id)
    {
        return DB::table('idiomas')->where('id', $id)->delete();
    }
}


