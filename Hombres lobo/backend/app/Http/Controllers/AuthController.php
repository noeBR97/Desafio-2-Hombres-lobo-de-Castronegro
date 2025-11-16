<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function registrar(Request $request)
    {
        $datos = $request->validate([
            'nombre'    => ['required', 'string', 'max:100'],
            'apellido1' => ['required', 'string', 'max:100'],
            'apellido2' => ['nullable', 'string', 'max:100'],
            'email'     => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'  => ['required', 'min:8'],
            'nick'      => ['required', 'string', 'max:50', 'unique:users,nick'],
        ]);

        try {
            $usuario = User::create([
            'nombre'    => $datos['nombre'],
            'apellido1' => $datos['apellido1'],
            'apellido2' => $datos['apellido2'] ?? null,
            'email'     => $datos['email'],
            'password'  => Hash::make($datos['password']),
            'nick'      => $datos['nick']
            ]);

            $token = $usuario->createToken('token_registro')->plainTextToken;

            return response()->json([
                'usuario' => $this->mapUsuario($usuario),
                'token'   => $token,
            ], 201);
        } catch(\Exception $e) {
            \Log::error('Error en registrar: ' . $e->getMessage());
            return response()->json([
                'error'=> $e->getMessage(),
            ], 500);
        }


    }

    public function login(Request $request)
    {
        $datos = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $usuario = User::where('email', $datos['email'])->first();

        if (!$usuario || !Hash::check($datos['password'], $usuario->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciales inválidas.'],
            ]);
        }


        $token = $usuario->createToken('token_login')->plainTextToken;

        return response()->json([
            'usuario' => $this->mapUsuario($usuario),
            'token'   => $token,
        ]);
    }

    public function perfil(Request $request)
    {
        return response()->json($this->mapUsuario($request->user()));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['mensaje' => 'Sesión cerrada']);
    }

    private function mapUsuario(User $u): array
    {
        return [
            'id'                  => $u->id,
            'nombre'              => $u->nombre,
            'apellido1'           => $u->apellido1,
            'apellido2'           => $u->apellido2,
            'nick'                => $u->nick,
            'email'               => $u->email,
            'partidas_jugadas'    => $u->partidas_jugadas,
            'partidas_ganadas'    => $u->partidas_ganadas,
            'partidas_perdidas'   => $u->partidas_perdidas,
            'created_at'          => $u->created_at,
            'updated_at'          => $u->updated_at,
        ];
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
