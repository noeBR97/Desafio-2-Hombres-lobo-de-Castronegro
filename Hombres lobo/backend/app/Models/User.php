<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'nombre',
        'apellido1',
        'apellido2',
        'email',
        'password',
        'nick',
        'partidas_jugadas',
        'partidas_ganadas',
        'partidas_perdidas',
    ];
    
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'partidas_jugadas'   => 'integer',
        'partidas_ganadas'   => 'integer',
        'partidas_perdidas'  => 'integer',
        'email_verified_at'  => 'datetime',
    ];
}
