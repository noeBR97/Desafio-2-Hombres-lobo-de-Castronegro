<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Registro de usuario
    public function register(Request $request)
    {
        $datos = $request->validate([
            'nombre'     => 'required|string|max:255',
            'apellido1'  => 'required|string|max:255',
            'apellido2'  => 'nullable|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:8',
            'nick'       => 'required|string|max:255|unique:users,nick',
        ]);

        $usuario = User::create([
            'nombre'     => $datos['nombre'],
            'apellido1'  => $datos['apellido1'],
            'apellido2'  => $datos['apellido2'] ?? null,
            'email'      => $datos['email'],
            'password'   => Hash::make($datos['password']),
            'nick'       => $datos['nick'] ?? null,
        ]);

        $token = $usuario->createToken('api')->plainTextToken;

        return response()->json([
            'user'  => $usuario,
            'token' => $token,
        ], 201);
    }

    // Inicio de sesión
    public function login(Request $request)
    {
        try {
            $datos = $request->validate([
                'email'    => 'required|email',
                'password' => 'required|string',
            ]);

            $usuario = User::where('email', $datos['email'])->first();

            if (!$usuario || !Hash::check($datos['password'], $usuario->password)) {
                return response()->json(['message' => 'Credenciales inválidas'], 401);
            }

            $token = $usuario->createToken('api')->plainTextToken;

            return response()->json([
                'user'  => $usuario,
                'token' => $token,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error'   => 'Error interno en login',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // Devuelve el usuario autenticado
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    // Cerrar sesión (revocar token actual)
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada']);
    }
}
