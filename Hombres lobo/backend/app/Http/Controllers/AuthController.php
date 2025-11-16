<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // REGISTRO (si lo necesitas)
    public function register(Request $request)
    {
        $datos = $request->validate([
            'nombre'     => 'required|string|max:255',
            'apellido1'  => 'required|string|max:255',
            'apellido2'  => 'nullable|string|max:255',
            'correo'     => 'required|email|unique:users,correo',
            'clave'      => 'required|string|min:4',
            'nick'       => 'required|string|max:255|unique:users,nick',
        ]);

        $usuario = User::create([
            'nombre'     => $datos['nombre'],
            'apellido1'  => $datos['apellido1'],
            'apellido2'  => $datos['apellido2'] ?? null,
            'correo'     => $datos['correo'],
            'nick'       => $datos['nick'],
            'clave'      => Hash::make($datos['clave']),
        ]);

        $token = Str::random(40);

        return response()->json([
            'user'  => $usuario,
            'token' => $token,
        ], 201);
    }

    // LOGIN
    public function login(Request $request)
    {
        $datos = $request->validate([
            'correo' => 'required|email',
            'clave'  => 'required|string',
        ]);

        $usuario = User::where('correo', $datos['correo'])->first();

        if (!$usuario) {
            return response()->json(['message' => 'Correo incorrecto'], 401);
        }

        // Comparar la clave en TEXTO con el HASH de la BD
        if (!Hash::check($datos['clave'], $usuario->clave)) {
            return response()->json(['message' => 'Contraseña incorrecta'], 401);
        }

        $token = Str::random(40);

        return response()->json([
            'message' => 'Login correcto',
            'token'   => $token,
            'user'    => $usuario,
        ], 200);
    }

    // Usuario autenticado (si en algún momento lo necesitas)
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    // Logout (placeholder mientras no uses Sanctum real)
    public function logout(Request $request)
    {
        return response()->json(['message' => 'Sesión cerrada']);
    }
}
