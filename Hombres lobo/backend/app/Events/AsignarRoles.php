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
        $partida->load('jugadores');

        $this->partida = [
            'id'        => $partida->id,
            'jugadores' => $partida->jugadores->map(function ($j) {
                return [
                    'id'   => $j->id,
                    'nick' => $j->nick,
                    'rol'  => $j->pivot->rol_partida,
                    'vivo' => (int) $j->pivot->vivo,
                ];
            })->toArray(),
        ];
    }

    public function broadcastOn()
    {
        return new PrivateChannel('lobby.' . $this->partida['id']);
    }

    public function broadcastAs()
    {
        return 'AsignarRoles';
    }
}
