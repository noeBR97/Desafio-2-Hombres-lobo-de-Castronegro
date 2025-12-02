<?php
namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Mensaje;

class GameMessageSent implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $mensaje;

    public function __construct(Mensaje $mensaje)
    {
        $this->mensaje = $mensaje;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('game.' . $this->mensaje->partida_id);
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'mensaje' => [
                'id' => $this->mensaje->id,
                'partida_id' => $this->mensaje->partida_id,
                'usuario_id' => $this->mensaje->usuario_id,
                'usuario_nick' => $this->mensaje->usuario->nick,
                'contenido' => $this->mensaje->contenido,
                'created_at' => $this->mensaje->created_at,
                'updated_at' => $this->mensaje->updated_at,
            ],
        ];
    }
}
