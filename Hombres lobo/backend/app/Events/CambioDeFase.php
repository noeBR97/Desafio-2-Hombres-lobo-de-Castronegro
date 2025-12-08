<?php

namespace App\Events;

use App\Models\Partida;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CambioDeFase implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $partida;

    public function __construct(Partida $partida)
    {
        $this->partida = $partida;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('game.' . $this->partida->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'CambioDeFase';
    }
}