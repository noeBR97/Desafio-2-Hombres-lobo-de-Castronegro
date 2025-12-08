<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JugadorPartida extends Model
{
    protected $table = 'jugadores_partida';

    protected $fillable = [
        'id_partida',
        'id_usuario',
        'nick_bot',
        'es_bot',
        'vivo',
        'rol_partida',
    ];

    protected $casts = [
        'es_bot' => 'boolean',
        'vivo' => 'boolean',
    ];
}
