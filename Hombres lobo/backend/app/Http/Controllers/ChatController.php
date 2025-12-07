<?php

namespace App\Http\Controllers;

use App\Events\GameMessageSent;
use Illuminate\Http\Request;
use App\Models\Mensaje;
use App\Models\JugadorPartida;

class ChatController extends Controller
{
        public function sendPrivate(Request $request)
    {
        $request->validate([
            'partida_id' => 'required|integer|exists:partidas,id',
            'contenido'  => 'required|string|max:1000',
        ]);
        $jugador = JugadorPartida::where('id_partida', $request->input('partida_id'))
            ->where('id_usuario', $request->user()->id)
            ->first();

        if (!$jugador || !$jugador->vivo) {
            return response()->json([
                'error' => 'Estás muerto, no puedes hablar.'
            ], 403);
        }

        $mensaje = Mensaje::create([
            'partida_id' => $request->input('partida_id'),
            'usuario_id' => $request->user()->id,
            'contenido'  => $request->input('contenido'),
        ]);

        event(new GameMessageSent($mensaje));

        return response()->json(['status' => 'ok', 'mensaje' => $mensaje], 201);
    }
}

