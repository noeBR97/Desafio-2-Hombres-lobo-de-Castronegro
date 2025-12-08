<?php

namespace App\Http\Controllers;

use App\Events\GameMessageSent;
use App\Models\JugadorPartida;
use App\Models\Mensaje;
use App\Models\Partida;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function sendPrivate(Request $request)
    {
        $request->validate([
            'partida_id' => 'required|integer|exists:partidas,id',
            'contenido'  => 'required|string|max:1000',
        ]);

        $user      = $request->user();
        $partidaId = $request->input('partida_id');

        $partida = Partida::findOrFail($partidaId);

        $jugador = JugadorPartida::where('id_partida', $partidaId)
            ->where('id_usuario', $user->id)
            ->first();

        if (!$jugador || !$jugador->vivo) {
            return response()->json([
                'error' => 'Estás muerto o no estás en esta partida.'
            ], 403);
        }

        $rol = strtolower(trim($jugador->rol_partida ?? ''));
        $soloLobos = false;

        if ($partida->fase_actual === 'noche') {
            if ($rol === 'lobo') {
                $soloLobos = true;
            } elseif ($rol === 'nina') {
                return response()->json([
                    'error' => 'La niña no puede hablar por la noche.'
                ], 403);
            } else {
                return response()->json([
                    'error' => 'Solo los lobos pueden hablar por la noche.'
                ], 403);
            }
        }
        $mensaje = Mensaje::create([
            'partida_id' => $partidaId,
            'usuario_id' => $user->id,
            'contenido'  => $request->input('contenido'),
        ]);

        event(new GameMessageSent($mensaje, $soloLobos));

        return response()->json([
            'status'  => 'ok',
            'mensaje' => $mensaje
        ], 201);
    }
}