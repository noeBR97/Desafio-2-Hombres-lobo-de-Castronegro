<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Mensaje extends Model
{
    use HasFactory;

    protected $fillable = [
        'partida_id',
        'usuario_id',
        'contenido',
        'timestamps',
    ];

    protected $casts = [
        'contenido'  => 'string',
    ];

    public function partida()
    {
        return $this->belongsTo(Partida::class, 'partida_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
