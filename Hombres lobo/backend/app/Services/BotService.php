<?php

namespace App\Services;

use App\Events\GameMessageSent;
use App\Models\JugadorPartida;
use App\Models\Mensaje;
use App\Models\Partida;
use App\Models\User;
use App\Models\VotoPartida;

class BotService
{
    private array $frasesInicioDia = [
        "¿Quién os parece más sospechoso hoy?",
        "No me fío nada de nadie…",
        "Yo creo que el lobo está muy callado.",
        "¿Y si votamos todos al mismo para probar?",
        "Alguien está mintiendo, lo noto.",
        "No sé ustedes, pero hay miradas muy raras hoy.",
        "¿Quién fue el que más habló ayer? Sospechoso.",
        "Esto huele a lobo… pero no sé de quién.",
        "Hablemos, que el tiempo se acaba.",
        "¿Quién acusa primero?"
    ];

    private array $frasesInicioNocheLobos = [
        "¿A quién nos comemos esta noche?",
        "Yo tengo hambre, decidid rápido.",
        "Alguien ha hablado demasiado hoy.",
        "Podríamos probar con alguien silencioso.",
        "Yo seguiría las sospechas del día.",
        "Que parezca un accidente.",
        "Esta noche nadie dormirá tranquilo.",
        "Uno más y estaremos más cerca de ganar.",
        "Que no sospechen de nosotros.",
        "Elegid bien, no tendremos muchas noches."
    ];

    public function lanzarFraseInicioDia(Partida $partida): void
    {
        $bot = JugadorPartida::where('id_partida', $partida->id)
            ->where('es_bot', true)
            ->where('vivo', true)
            ->inRandomOrder()
            ->first();

        if (!$bot || !$bot->id_usuario) {
            return;
        }

        $frase = $this->frasesInicioDia[array_rand($this->frasesInicioDia)];

        $mensaje = Mensaje::create([
            'partida_id' => $partida->id,
            'usuario_id' => $bot->id_usuario,
            'contenido'  => $frase,
        ]);

        event(new GameMessageSent($mensaje, false));
    }

    public function lanzarFraseInicioNocheLobos(Partida $partida): void
    {
        $bot = JugadorPartida::where('id_partida', $partida->id)
            ->where('es_bot', true)
            ->where('vivo', true)
            ->whereRaw('LOWER(rol_partida) = ?', ['lobo'])
            ->inRandomOrder()
            ->first();

        if (!$bot || !$bot->id_usuario) {
            return;
        }

        $frase = $this->frasesInicioNocheLobos[array_rand($this->frasesInicioNocheLobos)];

        $mensaje = Mensaje::create([
            'partida_id' => $partida->id,
            'usuario_id' => $bot->id_usuario,
            'contenido'  => $frase,
        ]);

        event(new GameMessageSent($mensaje, true));
    }

    public function reaccionarAlMensajeDia(Partida $partida, Mensaje $mensaje): void
    {
        if ($partida->fase_actual !== 'dia') {
            return;
        }

        $jugadoresVivos = JugadorPartida::where('id_partida', $partida->id)
            ->where('vivo', true)
            ->get();

        $bots = $jugadoresVivos->where('es_bot', true);

        if ($bots->isEmpty()) {
            return;
        }

        $objetivoDetectado = $this->detectarNombreJugadorEnMensaje($mensaje->contenido, $jugadoresVivos);

        foreach ($bots as $bot) {
            if ($this->botYaHaVotadoEnFase($partida, $bot, 'dia')) {
                continue;
            }

            if ($objetivoDetectado) {
                $this->decidirVotoConNombre($partida, $bot, $jugadoresVivos, $objetivoDetectado, 'dia');
            } else {
                $this->decidirVotoSinNombre($partida, $bot, $jugadoresVivos, 'dia');
            }
        }
    }

    public function reaccionarMensajeNocheLobos(Partida $partida, Mensaje $mensaje): void
    {
        if ($partida->fase_actual !== 'noche') {
            return;
        }

        $jugadoresVivos = JugadorPartida::where('id_partida', $partida->id)
            ->where('vivo', true)
            ->get();

        $botsLobos = $jugadoresVivos->filter(function ($j) {
            return $j->es_bot && strtolower((string)$j->rol_partida) === 'lobo';
        });

        if ($botsLobos->isEmpty()) {
            return;
        }

        $objetivoDetectado = $this->detectarNombreJugadorEnMensaje($mensaje->contenido, $jugadoresVivos);

        foreach ($botsLobos as $bot) {
            if ($this->botYaHaVotadoEnFase($partida, $bot, 'noche')) {
                continue;
            }

            if ($objetivoDetectado) {
                $this->decidirVotoConNombre($partida, $bot, $jugadoresVivos, $objetivoDetectado, 'noche');
            } else {
                $this->decidirVotoSinNombre($partida, $bot, $jugadoresVivos, 'noche');
            }
        }
    }

    private function detectarNombreJugadorEnMensaje(string $texto, $jugadores): ?JugadorPartida
    {
        $textoNorm = mb_strtolower($texto);

        $userIds = $jugadores->pluck('id_usuario')->filter()->unique()->values();

        if ($userIds->isEmpty()) {
            return null;
        }

        $usuarios = User::whereIn('id', $userIds)->get()->keyBy('id');

        foreach ($jugadores as $jugador) {
            if (!$jugador->id_usuario) {
                continue;
            }

            $usuario = $usuarios->get($jugador->id_usuario);
            if (!$usuario || !$usuario->nick) {
                continue;
            }

            $nick = mb_strtolower($usuario->nick);

            if (str_contains($textoNorm, $nick)) {
                return $jugador;
            }
        }

        return null;
    }

    private function botYaHaVotadoEnFase(Partida $partida, JugadorPartida $bot, string $tipoFase): bool
    {
        return VotoPartida::where('id_partida', $partida->id)
            ->where('id_jugador', $bot->id)
            ->where('tipo_fase', $tipoFase)
            ->where('ronda', $partida->ronda_actual)
            ->exists();
    }

    private function decidirVotoConNombre(
        Partida $partida,
        JugadorPartida $bot,
        $jugadores,
        JugadorPartida $objetivoDetectado,
        string $tipoFase
    ): void {
        $r = mt_rand(1, 100);

        if ($r <= 60) {
            $this->emitirVotoBot($partida, $bot, $objetivoDetectado, $tipoFase);
        } elseif ($r <= 90) {
            $objetivo = $this->elegirOtroJugadorAleatorio($jugadores, $bot, $objetivoDetectado);
            if ($objetivo) {
                $this->emitirVotoBot($partida, $bot, $objetivo, $tipoFase);
            }
        }
    }

    private function decidirVotoSinNombre(
        Partida $partida,
        JugadorPartida $bot,
        $jugadores,
        string $tipoFase
    ): void {
        $r = mt_rand(1, 100);

        if ($r <= 90) {
            $objetivo = $this->elegirJugadorAleatorio($jugadores, $bot);
            if ($objetivo) {
                $this->emitirVotoBot($partida, $bot, $objetivo, $tipoFase);
            }
        }
    }

    private function elegirJugadorAleatorio($jugadores, JugadorPartida $bot): ?JugadorPartida
    {
        $candidatos = $jugadores->where('id', '!=', $bot->id)->values();

        if ($candidatos->isEmpty()) {
            return null;
        }

        return $candidatos->random();
    }

    private function elegirOtroJugadorAleatorio(
        $jugadores,
        JugadorPartida $bot,
        JugadorPartida $excluir
    ): ?JugadorPartida {
        $candidatos = $jugadores
            ->where('id', '!=', $bot->id)
            ->where('id', '!=', $excluir->id)
            ->values();

        if ($candidatos->isEmpty()) {
            return null;
        }

        return $candidatos->random();
    }

    private function emitirVotoBot(
        Partida $partida,
        JugadorPartida $bot,
        JugadorPartida $objetivo,
        string $tipoFase
    ): void {
        VotoPartida::updateOrCreate(
            [
                'id_partida' => $partida->id,
                'id_jugador' => $bot->id,
                'tipo_fase'  => $tipoFase,
                'ronda'      => $partida->ronda_actual,
            ],
            [
                'id_objetivo' => $objetivo->id,
            ]
        );
    }
}