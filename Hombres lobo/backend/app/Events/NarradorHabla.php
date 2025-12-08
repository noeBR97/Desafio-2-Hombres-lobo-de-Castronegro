<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel; 
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NarradorHabla implements ShouldBroadcast
{
    public $mensaje;
    public $gameId;

    public function __construct($gameId, $mensaje)
    {
        $this->gameId = $gameId;
        $this->mensaje = $mensaje;
    }

    public function broadcastOn()
    {
        return new Channel("narrador.game.{$this->gameId}");
    }

    public function broadcastAs()
    {
        return 'NarradorHabla';
    }
}