<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PartidaIniciada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $gameId;

    public function __construct($gameId)
    {
        $this->gameId = $gameId;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('lobby.' . $this->gameId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'PartidaIniciada';
    }
}