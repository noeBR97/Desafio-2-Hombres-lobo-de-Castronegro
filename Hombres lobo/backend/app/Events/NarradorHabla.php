<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NarradorHabla implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $mensaje;
    public $gameId;
    public $soloLobos;

    /**
     * @param int 
     * @param string 
     * @param bool 
     */
    public function __construct(int $gameId, string $mensaje, bool $soloLobos = false)
    {
        $this->gameId = $gameId;
        $this->mensaje = $mensaje;
        $this->soloLobos = $soloLobos;
    }

    public function broadcastOn()
    {
        return new PrivateChannel("narrador.game.{$this->gameId}");
    }

    public function broadcastAs()
    {
        return 'NarradorHabla';
    }

    public function broadcastWith()
    {
        return [
            'mensaje'    => $this->mensaje,
            'solo_lobos' => $this->soloLobos,
        ];
    }
}