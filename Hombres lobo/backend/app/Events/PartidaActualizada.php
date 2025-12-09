<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PartidaActualizada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $partidaId;
    public array $jugadores;
    public ?array $jugador_muerto;

    public function __construct(int $partidaId, array $jugadores, ?array $jugadorMuerto = null)
    {
        $this->partidaId      = $partidaId;
        $this->jugadores      = $jugadores;
        $this->jugador_muerto = $jugadorMuerto;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('game.' . $this->partidaId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'PartidaActualizada';
    }

    public function broadcastWith(): array
    {
        return [
            'jugadores'      => $this->jugadores,
            'jugador_muerto' => $this->jugador_muerto,
        ];
    }
}