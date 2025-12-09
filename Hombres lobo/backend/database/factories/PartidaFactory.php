<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Partida;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Partida>
 */
class PartidaFactory extends Factory
{
    protected $model = Partida::class;

    public function definition(): array
    {
        return [
            'nombre_partida' => $this->faker->sentence(2),
            'estado' => 'en_espera',
            'id_creador_partida' => User::factory(),
            'numero_jugadores' => 0,
            'fecha_inicio' => null,
            'fase_actual' => 'noche',
            'ronda_actual' => 1,
            'fin_fase_at' => null,
            'ganador' => null,
            'max_jugadores' => $this->faker->numberBetween(15, 30),
        ];
    }
}
