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

    public int $partidaId;
    public string $fase;
    public int $ronda;

    public function __construct(Partida $partida)
    {
        $this->partidaId = $partida->id;
        $this->fase      = (string) $partida->fase_actual;
        $this->ronda     = (int) ($partida->ronda_actual ?? 1);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('game.' . $this->partidaId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'CambioDeFase';
    }

    public function broadcastWith(): array
    {
        return [
            'partida' => [
                'id'           => $this->partidaId,
                'fase_actual'  => $this->fase,
                'ronda_actual' => $this->ronda,
            ],
        ];
    }
}