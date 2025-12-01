<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Partida; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\JugadorUnido;
use App\Events\ActualizarListaPartidas;
use App\Events\JugadorSalio;

class PartidaController extends Controller
{
    public function index()
    {
        $partidas = Partida::with('jugadores')
            ->where('estado', 'en_espera')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($partidas);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nombre_partida' => 'required|string|max:100',
        ]);

        $user = Auth::user();

        $partida = Partida::create([
            'nombre_partida'       => $validatedData['nombre_partida'],
            'id_creador_partida'   => $user->id,
            'estado'               => 'en_espera',
            'numero_jugadores'     => 0
        ]);

        $partida->jugadores()->attach($user->id, [
            'es_bot'      => false,
            'vivo'        => true,
            'rol_partida' => 'sin_asignar',
        ]);

        $partida->load('jugadores');
        
        event(new ActualizarListaPartidas($partida));

        return response()->json($partida, 201);
    }

    public function show($id)
    {
        $partida = Partida::with('jugadores')->findOrFail($id);

        return response()->json($partida);
    }

    public function unirse(Request $request, $id)
    {
        $user = Auth::user();
        $partida = Partida::findOrFail($id);

        if (!$partida->jugadores()->where('id_usuario', $user->id)->exists()) {

            $partida->jugadores()->attach($user->id, [
                'es_bot'      => false,
                'vivo'        => true,
                'rol_partida' => 'sin_asignar',
            ]);

            $partida->increment('numero_jugadores');
        }
        
        $partida->load('jugadores');
        
        broadcast(new JugadorUnido($user, $partida->id))->toOthers();
        broadcast(new ActualizarListaPartidas($partida))->toOthers();

        return response()->json(['message' => 'Te has unido a la partida']);
    }

    public function salir($id)
    {
        try {
            $user = auth()->user();
            $partida = Partida::findOrFail($id);
            
            if ($partida->jugadores()->where('users.id', $user->id)->exists()) {
                $partida->jugadores()->detach($user->id);
                
                if ($partida->numero_jugadores > 0) {
                    $partida->decrement('numero_jugadores');
                }
            }
            
            $partida->load('jugadores');
            
            broadcast(new JugadorSalio($user, $partida->id))->toOthers();
            broadcast(new ActualizarListaPartidas($partida))->toOthers();
            
            return response()->json([
                'mensaje' => 'Has salido de la partida'
            ]);
        
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
