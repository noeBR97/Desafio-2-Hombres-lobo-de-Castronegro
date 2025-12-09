<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VotoPartida extends Model
{
    protected $table = 'votos_partida';

    protected $fillable = [
        'id_partida',
        'id_jugador',
        'id_objetivo',
        'tipo_fase',
        'ronda',
    ];
}
