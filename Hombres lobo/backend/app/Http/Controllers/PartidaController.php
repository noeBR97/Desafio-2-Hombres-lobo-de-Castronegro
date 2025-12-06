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
use App\Events\AsignarRoles;
use App\Events\AlcaldeElegido;
use App\Models\VotoPartida;
use Illuminate\Support\Facades\DB; 

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
            'es_alcalde'  => false,
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
                'es_alcalde'  => false,
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

public function iniciar(Request $request, $id)
{
    $partida = Partida::with('jugadores')->findOrFail($id);

    if ($partida->id_creador_partida !== Auth::id()) {
        return response()->json(['error' => 'No eres el lider'], 403);
    }

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

    if ($total >= 2) {
        $roles[] = 'nina';
    }

    while (count($roles) < $total) {
        $roles[] = 'aldeano';
    }

    shuffle($roles);

    foreach ($jugadores as $index => $jugador) {
        $partida->jugadores()->updateExistingPivot($jugador->id, [
            'rol_partida' => $roles[$index],
            'vivo'        => true,
            'es_alcalde'  => false,
        ]);
    }

    $partida->estado = 'en_curso';
    $partida->fecha_inicio = now();
    $partida->save();

    $partida->load('jugadores');

    event(new PartidaIniciada($partida->id));
    broadcast(new AsignarRoles($partida))->toOthers();

    $this->asignarAlcaldeAleatorio($partida);

    return response()->json([
        'ok'      => true,
        'mensaje' => 'Partida iniciada, roles y alcalde asignados',
    ]);
}


   public function estado($id)
{
    $partida = Partida::with('jugadores')->findOrFail($id);
    $hayAlcaldeVivo = $partida->jugadores->contains(function ($j) {
        return (int) $j->pivot->es_alcalde === 1
            && (int) $j->pivot->vivo === 1;
    });

    if (! $hayAlcaldeVivo) {
        $this->asignarAlcaldeAleatorio($partida);
        $partida->load('jugadores');
    }

    return response()->json([
        'id'             => $partida->id,
        'nombre_partida' => $partida->nombre_partida,
        'estado'         => $partida->estado,
        'jugadores'      => $partida->jugadores->map(function ($j) {
            return [
                'id'         => $j->id,
                'nick'       => $j->nick,
                'rol'        => $j->pivot->rol_partida,
                'vivo'       => (int) $j->pivot->vivo,
                'es_alcalde' => (int) $j->pivot->es_alcalde,
            ];
        }),
    ]);
}

private function asignarAlcaldeAleatorio(Partida $partida): ?int
{
    $partida->load('jugadores');

    $vivos = $partida->jugadores->filter(function ($j) {
        return (int) $j->pivot->vivo === 1;
    });

    if ($vivos->isEmpty()) {
        return null;
    }

    $alcalde = $vivos->random();

    foreach ($partida->jugadores as $jugador) {
        $partida->jugadores()->updateExistingPivot($jugador->id, [
            'es_alcalde' => $jugador->id === $alcalde->id,
        ]);
    }

    broadcast(new AlcaldeElegido($partida->id, $alcalde->id))->toOthers();

    return $alcalde->id;
}

public function votar(Request $request)
    {
        $request->validate([
            'partida_id' => 'required|exists:partidas,id',
            'voto_a'     => 'required|exists:users,id', 
        ]);

        $userId = Auth::id();
        $partidaId = $request->partida_id;
        $targetUserId = $request->voto_a;

        $jugadorVotante = DB::table('jugadores_partida')
                            ->where('id_partida', $partidaId)
                            ->where('id_usuario', $userId)
                            ->first();

        if (!$jugadorVotante || !$jugadorVotante->vivo) {
            return response()->json(['error' => 'No puedes votar (estás muerto o no juegas)'], 403);
        }

        $jugadorObjetivo = DB::table('jugadores_partida')
                            ->where('id_partida', $partidaId)
                            ->where('id_usuario', $targetUserId)
                            ->first();
                            
        if (!$jugadorObjetivo || !$jugadorObjetivo->vivo) {
            return response()->json(['error' => 'El objetivo no es válido'], 400);
        }

        VotoPartida::updateOrCreate(
            [
                'id_partida' => $partidaId,
                'id_jugador' => $jugadorVotante->id, 
                'ronda'      => 1, 
            ],
            [
                'id_objetivo' => $jugadorObjetivo->id,
                'tipo_fase'   => 'dia'
            ]
        );

        return response()->json(['message' => 'Voto registrado']);
    }
}