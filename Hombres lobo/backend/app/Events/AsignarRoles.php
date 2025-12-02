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
        $this->partida = $partida->load('jugadores');
    }

    public function broadcastOn()
    {
        return new PrivateChannel('lobby.' . $this->partida->id);
    }

    public function broadcastAs()
    {
        return 'AsignarRoles';
    }
}
