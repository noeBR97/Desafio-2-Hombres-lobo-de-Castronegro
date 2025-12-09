<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Partida;
use App\Models\JugadorPartida;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $usuarios = User::factory()->count(10)->create();

        $creadoresPartidas = $usuarios->random(4);

        // se van a crear 4 partidas asociadas a un id de un usuario existente
        foreach ($creadoresPartidas as $usuario) {
            Partida::factory()->create([
                'id_creador_partida' => $usuario->id,
            ]);
        }
    }
}
