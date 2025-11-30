<?php

namespace App\Http\Controllers;

use App\Events\GameMessageSent;
use Illuminate\Http\Request;
use App\Models\Mensaje;

class ChatController extends Controller
{
    public function sendPrivate(Request $request)
    {
        $request->validate([
            'partida_id' => 'required|integer|exists:partidas,id',
            'usuario_id' => 'required|integer|exists:users,id',
            'contenido'  => 'required|string|max:1000',
        ]);

        $mensaje = Mensaje::create([
            'partida_id' => $request->input('partida_id'),
            'usuario_id' => $request->user()->id,
            'contenido'  => $request->input('contenido'),
        ]);

        event(new GameMessageSent($mensaje));

        return response()->json(['status' => 'ok', 'mensaje' => $mensaje], 201);
    }
}

