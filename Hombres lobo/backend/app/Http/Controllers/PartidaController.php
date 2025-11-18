<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Partida; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PartidaController extends Controller
{
    public function index()
    {
        $partidas = Partida::where('estado', 'en_espera')
                            ->orderBy('created_at', 'desc') 
                            ->get();

        return response()->json($partidas);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nombre_partida' => 'required|string|max:100',
        ]);

        $user = Auth::user();

        $partida = Partida::create([
            'nombre_partida' => $validatedData['nombre_partida'],
            'id_creador_partida' => $user->id, 
            'estado' => 'en_espera',
            'numero_jugadores' => 0, 
        ]);

        return response()->json($partida, 201); 
    }
}
