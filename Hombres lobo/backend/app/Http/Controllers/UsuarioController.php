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
        $existe = User::where('email', $request->email)->exists();
        return response() -> json([
            'disponible' => !$existe
        ]);
    }
}
