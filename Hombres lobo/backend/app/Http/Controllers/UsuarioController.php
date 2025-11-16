<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

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
