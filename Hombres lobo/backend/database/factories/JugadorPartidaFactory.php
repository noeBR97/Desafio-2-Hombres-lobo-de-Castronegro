<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JugadorPartida>
 */
class JugadorPartidaFactory extends Factory
{
    protected $model = JugadorPartida::class;

    public function definition(): array
    {
        return [
            'id_partida' => Partida::factory(),
            'id_usuario' => User::factory(),
            'nick_bot' => null,
            'es_bot' => 0,
            'vivo' => 1,
            'es_alcalde' => 0,
            'rol_partida' => $this->faker->randomElement(['aldeano', 'lobo', 'nina']),
        ];
    }
}
