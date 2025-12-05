<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class ActualizarTiempo implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $gameId;
    public $tiempoRestante;

    public function __construct($gameId, $tiempoRestante)
    {
        $this->gameId = $gameId;
        $this->tiempoRestante = $tiempoRestante;
    }

    public function broadcastOn()
    {
        return new PrivateChannel("game.$this->gameId");
    }

    public function broadcastAs()
    {
        return 'tiempo.actualizado';
    }
}
