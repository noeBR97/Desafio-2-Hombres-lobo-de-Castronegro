<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AlcaldeElegido implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public int $partida_id;
    public int $jugador_id;
    public $jugador_nick;

    public function __construct(int $partida_id, int $jugador_id)
    {
        $this->partida_id = $partida_id;
        $this->jugador_id = $jugador_id;
        $this->jugador_nick = $jugador_nick;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('game.' . $this->partida_id);
    }

    public function broadcastAs()
    {
        return 'AlcaldeElegido';
    }

    public function broadcastWith()
    {
        return [
            'partida_id' => $this->partida_id,
            'jugador_id' => $this->jugador_id,
        ];
    }
}
