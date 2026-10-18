<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function perfil(Request $request): JsonResponse
    {
        // Retorna los datos del usuario dueño del token actual
        return response()->json($request->user());
    }
}
