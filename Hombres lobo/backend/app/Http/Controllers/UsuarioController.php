<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UsuarioController extends Controller
{
    public function validarUserName(Request $request) {
        $existe = User::where('nick', $request->nick)->exists();
        return response() -> json([
            'disponible' => !$existe
        ]);
    }

    public function validarEmail(Request $request) {
        $existe = User::where('correo', $request->correo)->exists();
        return response() -> json([
            'disponible' => !$existe
        ]);
    }

    public function actualizarImagenPerfil(Request $request) {
        $messages = [
        'imagen-perfil.required' => 'Falta el archivo',
        'imagen-perfil.mimes' => 'Tipo no soportado',
        'imagen-perfil.max' => 'El archivo excede el tamaño máximo permitido',
        ];

        $validator = Validator::make($request->all(), [
                'imagen-perfil' => 'required|image|mimes:jpeg,png,jpg,gif|max:4096'
        ], $messages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->hasFile('imagen-perfil') && $request->file('imagen-perfil')->isValid()) {
            try {
                $file = $request->file('imagen-perfil');

                //nombre y extension del archivo
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();

                //nombre único y seguro
                $filename = uniqid('img_') . '_' . Str::slug($originalName) . '.' . $extension;

                //subir archivo usando el disco cloudinary
                $uploadedFilePath = Storage::disk('cloudinary')->putFileAs('laravel', $file, $filename);

                //url pública
                $url = Storage::disk('cloudinary')->url($uploadedFilePath);

                $usuario = $request->user();
                $usuario->avatar_url = $url;
                $usuario->save();

                return response()->json(['url' => $url], 200);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Error al subir la imagen' . $e->getMessage()], 500);
            }
        }
        return response()->json(['error' => 'No se recibió ningún archivo.'], 400);
    }

    public function listaAvatares() {
        $avatares = [
            'avatar-aldeano.png',
            'avatar-bruja.png',
            'avatar-cazador.png',
            'avatar-cupido.png',
            'avatar-ladron.png',
            'avatar-lobo.png',
            'avatar-nina.png',
            'avatar-vidente.png',
            'avatar-oficial.jpg',
            'AVATAR-USUARIO.png'
        ];

        return response() -> json($avatares,200);
    }

    public function elegirAvatar(Request $request) {
        $request ->validate([
            'avatar' => 'required|string'
        ]);
        $usuario = $request->user();

        $usuario->avatar_predefinido = $request->avatar;
        $usuario->avatar_url = null;
        $usuario->save();

        return response()->json(['ok' => true,
                                'nombre-avatar' => $usuario->avatar_predefinido], 200);
    }

    public function update(Request $request) {
        $usuario = $request->user();

        $data = $request->validate([
            'nick' => 'string|max:50|unique:users,nick,'.$usuario->id,
            'clave' => 'min:8',
        ]);

        if (isset($data['nick'])) {
            $usuario->nick = $data['nick'];
        }

        if (isset($data['clave']) && $data['clave'] != null) {
            $usuario->clave = bcrypt($data['clave']);
        }

        $usuario->save();

        return response()->json(['mensaje'=> 'Usuario actualizado correctamente', 'usuario' => $usuario], 200);
    }

    public function index()
    {
        // Devuelve lista paginada y sin campos sensibles (password ya está oculto por defecto)
        return User::select(
                'id','nombre','apellido1','apellido2','correo','nick',
                'partidas_jugadas','partidas_ganadas','partidas_perdidas',
                'created_at','updated_at'
            )->get();
    }
}
