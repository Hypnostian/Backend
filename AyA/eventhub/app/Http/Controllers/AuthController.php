<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        // Mismo mensaje para correo inexistente o contraseña errónea: no revela qué cuentas existen
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Usuario no encontrado o contraseña incorrecta',
            ], 401);
        }

        // El método createToken() está disponible gracias a HasApiTokens en el Modelo User
        $token = $user->createToken('token-eventhub')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
}
