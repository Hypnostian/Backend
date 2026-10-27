<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Tokens de Sanctum para consumir la API desde Postman, curl u otra aplicación.
 */
class AuthController extends Controller
{
    /**
     * POST /api/login
     */
    public function login(Request $request): JsonResponse
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credenciales['email'])->first();

        // Mismo mensaje para correo inexistente o contraseña errónea: no revela qué cuentas existen
        if (! $user || ! Hash::check($credenciales['password'], $user->password)) {
            return response()->json([
                'message' => __('auth.failed'),
            ], 401);
        }

        return response()->json([
            'token' => $user->createToken('medicitas-api')->plainTextToken,
            'token_type' => 'Bearer',
            'usuario' => [
                'id' => $user->id,
                'nombre' => $user->name,
                'rol' => $user->rol,
            ],
        ]);
    }

    /**
     * POST /api/logout: revoca el token con el que se hizo la petición.
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json(['message' => 'Sesión cerrada.']);
    }
}
