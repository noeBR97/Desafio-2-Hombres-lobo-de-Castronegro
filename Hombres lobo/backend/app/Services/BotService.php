<?php

namespace App\Services;

use App\Models\Partida;
use App\Models\Mensaje;
use App\Models\JugadorPartida;
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
        "Yo creo que son Fernando, Inma, Gema y Antonio.",
        "Esto huele a lobo… pero no sé de quién.",
        "Hablemos, que el tiempo se acaba.",
        "¿Quién acusa primero?",
    ];

    public function lanzarFraseInicioDia(Partida $partida): void
    {
        $bot = JugadorPartida::where('id_partida', $partida->id)
            ->where('es_bot', true)
            ->where('vivo', true)
            ->inRandomOrder()
            ->first();

        if (!$bot) {
            return;
        }

        $frase = $this->frasesInicioDia[array_rand($this->frasesInicioDia)];

        $mensaje = Mensaje::create([
            'id_partida' => $partida->id,
            'id_jugador' => $bot->id,
            'contenido'  => $frase,
            'tipo'       => 'dia',
        ]);

    }

    public function reaccionarAlMensajeDia(Partida $partida, Mensaje $mensaje): void
    {
        if ($partida->fase !== 'dia') {
            return;
        }

        $jugadoresVivos = JugadorPartida::where('id_partida', $partida->id)
            ->where('vivo', true)
            ->get();

        $bots = $jugadoresVivos->where('es_bot', true);

        if ($bots->isEmpty()) {
            return;
        }

        $objetivoDetectado = $this->detectarNombreJugadorEnMensaje(
            $mensaje->contenido,
            $jugadoresVivos
        );

        foreach ($bots as $bot) {

            if ($this->botYaHaVotadoHoy($partida, $bot)) {
                continue;
            }

            if ($objetivoDetectado) {
                $this->decidirVotoConNombre($partida, $bot, $jugadoresVivos, $objetivoDetectado);
            } else {
                $this->decidirVotoSinNombre($partida, $bot, $jugadoresVivos);
            }
        }
    }

    private function detectarNombreJugadorEnMensaje(string $texto, $jugadores)
    {
        $textoNorm = mb_strtolower($texto);

        foreach ($jugadores as $jugador) {
            $nick = mb_strtolower($jugador->nick);

            if (str_contains($textoNorm, $nick)) {
                return $jugador;
            }
        }

        return null;
    }

    private function botYaHaVotadoHoy(Partida $partida, JugadorPartida $bot): bool
    {
        return VotoPartida::where('id_partida', $partida->id)
            ->where('id_jugador', $bot->id)
            ->where('fase', 'dia')
            ->exists();
    }

    private function decidirVotoConNombre(
        Partida $partida,
        JugadorPartida $bot,
        $jugadores,
        JugadorPartida $objetivoDetectado
    ): void {
        $r = mt_rand(1, 100);

        if ($r <= 60) {
            $this->emitirVotoBot($partida, $bot, $objetivoDetectado);

        } elseif ($r <= 90) {
            $objetivo = $this->elegirOtroJugadorAleatorio($jugadores, $bot, $objetivoDetectado);
            if ($objetivo) {
                $this->emitirVotoBot($partida, $bot, $objetivo);
            }

        } else {
            return;
        }
    }

    private function decidirVotoSinNombre(
        Partida $partida,
        JugadorPartida $bot,
        $jugadores
    ): void {
        $r = mt_rand(1, 100);

        if ($r <= 90) {
            $objetivo = $this->elegirJugadorAleatorio($jugadores, $bot);
            if ($objetivo) {
                $this->emitirVotoBot($partida, $bot, $objetivo);
            }
        }
    }

    private function elegirJugadorAleatorio($jugadores, JugadorPartida $bot)
    {
        $candidatos = $jugadores
            ->where('id', '!=', $bot->id)
            ->values();

        if ($candidatos->isEmpty()) {
            return null;
        }

        return $candidatos->random();
    }

    private function elegirOtroJugadorAleatorio(
        $jugadores,
        JugadorPartida $bot,
        JugadorPartida $excluir
    ) {
        $candidatos = $jugadores
            ->where('id', '!=', $bot->id)
            ->where('id', '!=', $excluir->id)
            ->values();

        if ($candidatos->isEmpty()) {
            return null;
        }

        return $candidatos->random();
    }

    private function emitirVotoBot(Partida $partida, JugadorPartida $bot, JugadorPartida $objetivo): void
    {
        VotoPartida::updateOrCreate(
            [
                'id_partida' => $partida->id,
                'id_jugador' => $bot->id,
                'fase'       => 'dia',
            ],
            [
                'id_objetivo' => $objetivo->id,
            ]
        );
    }
}
