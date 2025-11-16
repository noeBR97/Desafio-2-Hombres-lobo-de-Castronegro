<?php

namespace App\Http\Controllers;

use App\Models\User;

class UsersController extends Controller
{
    public function index()
    {
        // Devuelve lista paginada y sin campos sensibles (password ya está oculto por defecto)
        return User::select(
                'id','nombre','apellido1','apellido2','correo','nick',
                'partidas_jugadas','partidas_ganadas','partidas_perdidas',
                'created_at','updated_at'
            )->get();
    }
}