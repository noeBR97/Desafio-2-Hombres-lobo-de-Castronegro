<?php

namespace App\Events;

use App\Models\Partida;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AsignarRoles implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $partida;

    public function __construct(Partida $partida)
    {
        // Enviamos la partida con sus jugadores al front
        $this->partida = $partida->load('jugadores');
    }

    public function broadcastOn()
    {
        // Mismo canal que usas en lobby.ts: `lobby.${gameId}`
        return new PrivateChannel('lobby.' . $this->partida->id);
    }

    public function broadcastAs()
    {
        // Mismo nombre que escuchas en el front: `.PartidaIniciada`
        return 'PartidaIniciada';
    }
}
