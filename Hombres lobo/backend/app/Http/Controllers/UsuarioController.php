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
