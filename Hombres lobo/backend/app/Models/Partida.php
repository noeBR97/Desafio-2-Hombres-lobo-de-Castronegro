<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partida extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre_partida',
        'id_creador_partida',
        'estado',
        'numero_jugadores',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
    ];

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_creador_partida');
    }

}
