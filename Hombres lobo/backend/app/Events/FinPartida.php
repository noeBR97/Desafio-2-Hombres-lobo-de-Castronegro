<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FinPartida implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $partidaId;
    public $mensaje;
    public $ganadores;

    public function __construct($partidaId, $mensaje, $ganadores)
    {
        $this->partidaId = $partidaId;
        $this->mensaje = $mensaje;
        $this->ganadores = $ganadores;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('game.' . $this->partidaId);
    }

    public function broadcastAs()
    {
        return 'fin.partida';
    }
}
