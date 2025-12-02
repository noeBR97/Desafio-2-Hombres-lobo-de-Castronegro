<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Partida; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\JugadorUnido;
use App\Events\ActualizarListaPartidas;
use App\Events\JugadorSalio;
use App\Events\PartidaIniciada; 

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

      public function roles($id)
{
    $partida = Partida::with('jugadores')->findOrFail($id);

    $jugadores = $partida->jugadores;

    $total = $jugadores->count();
    if ($total >= 12) {
        $numLobos = 3;
    } elseif ($total >= 8) {
        $numLobos = 2;
    } else {
        $numLobos = 1;
    }

    $roles = [];

    for ($i = 0; $i < $numLobos; $i++) {
        $roles[] = 'lobo';
    }

    while (count($roles) < $total) {
        $roles[] = 'aldeano';
    }

    shuffle($roles);

    foreach ($jugadores as $index => $jugador) {
        $partida
            ->jugadores()
            ->updateExistingPivot($jugador->id, [
                'rol_partida' => $roles[$index],
                'vivo'        => true,
            ]);
    }

    $partida->estado = 'noche_1';
    $partida->fecha_inicio = now();
    $partida->save();

    return response()->json([
        'ok' => true,
        'mensaje' => 'Roles asignados correctamente',
        'estado' => $partida->estado,
    ]);
}

    public function iniciar(Request $request, $id)
    {
        $partida = Partida::findOrFail($id);
        
        if ($partida->id_creador_partida !== Auth::id()) {
            return response()->json(['error' => 'No eres el líder'], 403);
        }

        $partida->estado = 'en_curso';
        $partida->save();

        event(new PartidaIniciada($partida->id));

        return response()->json(['message' => 'Partida iniciada']);
    }

  }
