<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function registrar(Request $request)
    {
        $datos = $request->validate([
            'nombre'    => ['required', 'string', 'max:100'],
            'apellido1' => ['required', 'string', 'max:100'],
            'apellido2' => ['nullable', 'string', 'max:100'],
            'correo'    => ['required', 'email', 'max:255', 'unique:users,correo'],
            'clave'     => ['required', 'min:8'],
            'nick'      => ['required', 'string', 'max:50', 'unique:users,nick'],
        ]);

        try {
            $usuario = User::create([
            'nombre'    => $datos['nombre'],
            'apellido1' => $datos['apellido1'],
            'apellido2' => $datos['apellido2'] ?? null,
            'correo'     => $datos['correo'],
            'clave'  => Hash::make($datos['clave']),
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
// LOGIN
    public function login(Request $request)
{
    $cred = $request->validate([
        'correo' => ['required', 'email'],
        'clave'  => ['required', 'string'],
    ]);

    $user = User::where('correo', $cred['correo'])->first();

    if (!$user || !Hash::check($cred['clave'], $user->clave)) {
        return response()->json([
            'ok' => false,
            'message' => 'Correo o contraseña incorrectos',
        ], 401);
    }

    return response()->json([
        'ok' => true,
        'user' => [
            'id'        => $user->id,
            'nick'      => $user->nick,
            'correo'    => $user->correo,
            'rol_corp'  => $user->rol_corp
        ]
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
            'correo'              => $u->correo,
            'partidas_jugadas'    => $u->partidas_jugadas,
            'partidas_ganadas'    => $u->partidas_ganadas,
            'partidas_perdidas'   => $u->partidas_perdidas,
            'created_at'          => $u->created_at,
            'updated_at'          => $u->updated_at,
        ];
    }}