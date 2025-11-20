<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
 //Funcion para mostrar todos los usuarios
    public function get_all()
    {
        $usuarios = User::select(
            'id',
            'nombre',
            'apellido1',
            'apellido2',
            'nick',
            'correo',
            'rol_corp'
        )->get();

        return response()->json($usuarios);
    }
//Funcion para filtrar usuarios
    public function get_one(User $user)
    {
        return response()->json([
            'id'        => $user->id,
            'nombre'    => $user->nombre,
            'apellido1' => $user->apellido1,
            'apellido2' => $user->apellido2,
            'nick'      => $user->nick,
            'correo'    => $user->correo,
            'rol_corp'  => (bool) $user->rol_corp,
        ]);
    }
//Funcion para buscar usuarios por ID, correo o nick
    public function buscar(Request $request)
    {
        $busqueda = trim($request->query('busqueda', ''));

        if ($busqueda === '') {
            return response()->json([
                'message' => 'Falta el parámetro "busqueda" en la URL',
            ], 422);
        }

        $query = User::query();

        if (is_numeric($busqueda)) {
            $query->where('id', $busqueda);
        }
        elseif (str_contains($busqueda, '@')) {
            $query->where('correo', $busqueda);
        }
        else {
            $query->where('nick', $busqueda)
                  ->orWhere('correo', $busqueda);
        }

        $user = $query->first();

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado',
            ], 404);
        }

        return response()->json([
            'id'        => $user->id,
            'nombre'    => $user->nombre,
            'apellido1' => $user->apellido1,
            'apellido2' => $user->apellido2,
            'nick'      => $user->nick,
            'correo'    => $user->correo,
            'rol_corp'  => (bool) $user->rol_corp,
        ]);
    }
//Funcion para actualizar los campos
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'nombre' => ['nullable', 'string', 'max:255'],
            'apellido1' => ['nullable', 'string', 'max:255'],
            'apellido2' => ['nullable', 'string', 'max:255'],

            'nick' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'nick')->ignore($user->id),
            ],

            'correo' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'correo')->ignore($user->id),
            ],

            'clave' => [
                'nullable',
                'string',
                'min:6',
            ],

            'rol_corp' => ['nullable', 'boolean'],
        ]);

        foreach (['nombre', 'apellido1', 'apellido2', 'nick', 'correo', 'rol_corp'] as $campo) {
            if (array_key_exists($campo, $data)) {
                $user->$campo = $data[$campo];
            }
        }

        if (!empty($data['clave'])) {
            $user->password = Hash::make($data['clave']);
        }

        $user->save();

        return response()->json([
            'message' => 'Usuario actualizado correctamente',
            'user'    => [
                'id'        => $user->id,
                'nombre'    => $user->nombre,
                'apellido1' => $user->apellido1,
                'apellido2' => $user->apellido2,
                'nick'      => $user->nick,
                'correo'    => $user->correo,
                'rol_corp'  => (bool) $user->rol_corp,
            ],
        ]);
    }
//Funcion para borrar un usuario
    public function delete(User $user)
    {
        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado correctamente',
        ]);
    }
}