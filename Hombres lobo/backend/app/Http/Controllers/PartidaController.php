<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Partida;
use App\Models\JugadorPartida;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\JugadorUnido;
use App\Events\ActualizarListaPartidas;
use App\Events\JugadorSalio;
use App\Events\PartidaIniciada;
use App\Events\AsignarRoles;
use App\Events\AlcaldeElegido;
use App\Events\PartidaActualizada;
use App\Models\VotoPartida;
use Illuminate\Support\Facades\DB; 
use App\Events\CambioDeFase;
use App\Services\BotService;

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
            'numero_jugadores' => 'required|integer|min:15|max:30' //validar lo que el creador elige
        ]);

        $user = Auth::user();

        $partida = Partida::create([
            'nombre_partida'       => $validatedData['nombre_partida'],
            'id_creador_partida'   => $user->id,
            'estado'               => 'en_espera',
            'numero_jugadores'     => 0,
            'max_jugadores'     => $validatedData['numero_jugadores']
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
    $partida = Partida::findOrFail($id);

    if ($partida->id_creador_partida !== Auth::id()) {
        return response()->json(['error' => 'No eres el lider'], 403);
    }

    $this->rellenarConBots($partida->id);

    $jugadoresPartida = JugadorPartida::where('id_partida', $partida->id)->get();

    $humanos      = $jugadoresPartida->where('es_bot', false);
    $totalHumanos = $humanos->count();
    $total        = $jugadoresPartida->count();

    if ($total >= 12) {
        $numLobos = 4;
    } elseif ($total >= 8) {
        $numLobos = 2;
    } else {
        $numLobos = 1;
    }

    $idNina = null;
    if ($totalHumanos >= 2) {
        $nina   = $humanos->random();
        $idNina = $nina->id;
    }

    $candidatos = $jugadoresPartida
        ->where('id', '!=', $idNina)
        ->values();

    $humanosCandidatos = $candidatos->where('es_bot', false);

    $humanoLoboId = null;
    if ($numLobos > 0 && $humanosCandidatos->isNotEmpty()) {
        $humanoLoboId = $humanosCandidatos->random()->id;
    }

    $rolesPool = [];

    $lobosRestantes = $numLobos - ($humanoLoboId ? 1 : 0);

    for ($i = 0; $i < $lobosRestantes; $i++) {
        $rolesPool[] = 'lobo';
    }

    $slotsRestantes = $candidatos->count() - ($humanoLoboId ? 1 : 0);

    while (count($rolesPool) < $slotsRestantes) {
        $rolesPool[] = 'aldeano';
    }

    shuffle($rolesPool);

    foreach ($candidatos as $jug) {
        if ($jug->id === $humanoLoboId) {
            $rolAsignado = 'lobo';
        } else {
            $rolAsignado = array_pop($rolesPool) ?? 'aldeano';
        }

        $jug->rol_partida = $rolAsignado;
        $jug->vivo        = true;
        $jug->es_alcalde  = false;
        $jug->save();
    }

    if ($idNina !== null) {
        JugadorPartida::where('id', $idNina)->update([
            'rol_partida' => 'nina',
            'vivo'        => true,
            'es_alcalde'  => false,
        ]);
    }

    $partida->estado        = 'en_curso';
    $partida->fecha_inicio  = now();
    $partida->fase_actual   = 'noche';
    $partida->ronda_actual  = $partida->ronda_actual ?? 1;
    $partida->save();

    $partida->load('jugadores');

    event(new PartidaIniciada($partida->id));
    broadcast(new AsignarRoles($partida))->toOthers();

    $this->asignarAlcaldeAleatorio($partida);

    $botService = app(\App\Services\BotService::class);
    $botService->lanzarFraseInicioNocheLobos($partida);

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

    if (!$hayAlcaldeVivo) {
        $this->asignarAlcaldeAleatorio($partida);
        $partida->load('jugadores');
    }

    $jugadores = $partida->jugadores->map(function ($j) {
        return [
            'id'         => $j->pivot->id,
            'id_usuario' => $j->id,
            'nick'       => $j->nick,
            'rol'        => $j->pivot->rol_partida,
            'vivo'       => (int) $j->pivot->vivo,
            'es_alcalde' => (int) $j->pivot->es_alcalde,
            'es_bot'     => (int) $j->pivot->es_bot,
        ];
    })->toArray();

    $bots = \DB::table('jugadores_partida')
        ->where('id_partida', $id)
        ->where('es_bot', true)
        ->whereNull('id_usuario')
        ->get();

    foreach ($bots as $bot) {
        $jugadores[] = [
            'id'         => $bot->id,
            'id_usuario' => null,
            'nick'       => $bot->nick_bot,
            'rol'        => $bot->rol_partida,
            'vivo'       => (int) $bot->vivo,
            'es_alcalde' => (int) $bot->es_alcalde,
            'es_bot'     => (int) $bot->es_bot,
        ];
    }

    return response()->json([
        'id'             => $partida->id,
        'nombre_partida' => $partida->nombre_partida,
        'estado'         => $partida->estado,
        'jugadores'      => $jugadores,
    ]);
}


private function asignarAlcaldeAleatorio(Partida $partida): ?int
{
    $partida->load('jugadores');

    $vivos = $partida->jugadores->filter(function ($j) {
        return (int) $j->pivot->vivo === 1 && !$j->pivot->es_bot;
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
        'voto_a'     => 'required|exists:jugadores_partida,id',
    ]);

    $partida   = Partida::findOrFail($request->partida_id);
    $partidaId = $partida->id;
    $userId    = Auth::id();
    $targetJugadorId = $request->voto_a;

    $jugadorVotante = JugadorPartida::where('id_partida', $partidaId)
        ->where('id_usuario', $userId)
        ->first();

    if (!$jugadorVotante || !$jugadorVotante->vivo) {
        return response()->json(['error' => 'No puedes votar (estás muerto o no juegas)'], 403);
    }

    if ($partida->fase_actual === 'noche' && $jugadorVotante->rol_partida !== 'lobo') {
        return response()->json(['error' => 'Solo los lobos pueden votar por la noche'], 403);
    }

    $jugadorObjetivo = JugadorPartida::where('id_partida', $partidaId)
        ->where('id', $targetJugadorId)
        ->first();

    if (!$jugadorObjetivo || !$jugadorObjetivo->vivo) {
        return response()->json(['error' => 'El objetivo no es válido'], 400);
    }

    VotoPartida::updateOrCreate(
        [
            'id_partida' => $partidaId,
            'id_jugador' => $jugadorVotante->id,
            'ronda'      => $partida->ronda_actual,
        ],
        [
            'id_objetivo' => $jugadorObjetivo->id,
            'tipo_fase'   => $partida->fase_actual,
        ]
    );

    return response()->json(['message' => 'Voto registrado']);
}

private function rellenarConBots(int $idPartida): void
{
    $partida = Partida::with('jugadores')->findOrFail($idPartida);

    $maxJugadores = $partida->max_jugadores ?? 12;

    $totalActual = $partida->jugadores->count();
    if ($totalActual >= $maxJugadores) {
        return;
    }

    $jugadoresFaltan = $maxJugadores - $totalActual;

    $idsUsuariosOcupados = $partida->jugadores
        ->pluck('id_usuario')
        ->filter()
        ->unique()
        ->values()
        ->all();

    $usuariosBotDisponibles = User::where('rol_corp', 'bot')
        ->whereNotIn('id', $idsUsuariosOcupados)
        ->inRandomOrder()
        ->take($jugadoresFaltan)
        ->get();

    if ($usuariosBotDisponibles->isEmpty()) {
        return;
    }

    foreach ($usuariosBotDisponibles as $userBot) {
        if ($totalActual >= $maxJugadores) {
            break;
        }

        $jp = new JugadorPartida();
        $jp->id_partida  = $partida->id;
        $jp->id_usuario  = $userBot->id;
        $jp->es_bot      = true;
        $jp->vivo        = true;
        $jp->es_alcalde  = false;
        $jp->rol_partida = 'sin_asignar';
        $jp->save();

        $totalActual++;
    }

    $partida->numero_jugadores = $totalActual;
    $partida->save();

    broadcast(new PartidaActualizada($partida->id))->toOthers();
}

public function siguienteFase(Request $request, $id)
{
    try {
        $partida = Partida::findOrFail($id);
        $faseCliente = $request->input('fase_actual_cliente');

        if ($partida->fase_actual !== $faseCliente) {
            return response()->json([
                'mensaje' => 'La fase ya había cambiado, ignorando petición.',
                'fase'    => $partida->fase_actual,
                'ronda'   => $partida->ronda_actual,
            ], 200);
        }

        $faseQueTermina = $partida->fase_actual;

        $votos = VotoPartida::where('id_partida', $partida->id)
            ->where('tipo_fase', $faseQueTermina)
            ->where('ronda', $partida->ronda_actual)
            ->get();

        if ($votos->count() > 0) {
            $conteo = [];

            foreach ($votos as $voto) {
                if ($voto->id_objetivo === null) {
                    continue;
                }

                $jugadorVotante = JugadorPartida::find($voto->id_jugador);
                if (!$jugadorVotante || !$jugadorVotante->vivo) {
                    continue;
                }

                $peso = 1;
                if (
                    $faseQueTermina === 'dia' &&
                    !empty($jugadorVotante->es_alcalde) &&
                    (int)$jugadorVotante->es_alcalde === 1
                ) {
                    $peso = 2;
                }

                if (!isset($conteo[$voto->id_objetivo])) {
                    $conteo[$voto->id_objetivo] = 0;
                }
                $conteo[$voto->id_objetivo] += $peso;
            }

            if (!empty($conteo)) {
                $maxVotos     = max($conteo);
                $candidatosId = array_keys($conteo, $maxVotos);
                $idMasVotado  = $candidatosId[0];

                $jugadorObjetivo = JugadorPartida::find($idMasVotado);
                if ($jugadorObjetivo && $jugadorObjetivo->vivo) {
                    $jugadorObjetivo->vivo = false;
                    $jugadorObjetivo->save();
                }
            }

            VotoPartida::where('id_partida', $partida->id)
                ->where('tipo_fase', $faseQueTermina)
                ->where('ronda', $partida->ronda_actual)
                ->delete();
        }

        $faseAnterior = $partida->fase_actual;

        if ($partida->fase_actual === 'noche') {
            $partida->fase_actual = 'dia';
        } else {
            $partida->fase_actual = 'noche';
            $partida->ronda_actual = $partida->ronda_actual ? $partida->ronda_actual + 1 : 1;
        }

        $partida->save();
        $partida->load('jugadores');

        $botService = app(\App\Services\BotService::class);

        try {
            if ($faseAnterior === 'noche' && $partida->fase_actual === 'dia') {
                $botService->lanzarFraseInicioDia($partida);
            } elseif ($faseAnterior === 'dia' && $partida->fase_actual === 'noche') {
                $botService->lanzarFraseInicioNocheLobos($partida);
            }
        } catch (\Throwable $e) {
            \Log::error('Error en BotService al cambiar de fase: '.$e->getMessage());
        }

            try {
                broadcast(new CambioDeFase($partida))->toOthers();
            } catch (\Throwable $e) {
                \Log::error('Error al emitir CambioDeFase: '.$e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);
            }

        return response()->json([
            'mensaje' => 'Fase actualizada correctamente',
            'fase'    => $partida->fase_actual,
            'ronda'   => $partida->ronda_actual,
        ]);

    } catch (\Throwable $e) {
        \Log::error('Error en siguienteFase: '.$e->getMessage(), [
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'mensaje' => 'Error interno al cambiar de fase',
            'error'   => $e->getMessage(),
        ], 500);
    }
}
}