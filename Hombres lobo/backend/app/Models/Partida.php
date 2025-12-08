<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Partida extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre_partida',
        'id_creador_partida',
        'estado',
        'numero_jugadores',
        'max_jugadores'
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
    ];

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_creador_partida');
    }

    public function jugadores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'jugadores_partida', 'id_partida', 'id_usuario')
                    ->withPivot('es_bot', 'vivo', 'rol_partida', 'es_alcalde', 'nick_bot');
    }

}
