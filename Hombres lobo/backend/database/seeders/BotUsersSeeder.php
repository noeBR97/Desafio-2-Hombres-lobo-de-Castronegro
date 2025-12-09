<?php

namespace Database\Seeders;

use App\Models\User;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BotUsersSeeder extends Seeder
{
    public function run(): void
    {
        $faker = FakerFactory::create('es_ES');

        for ($i = 1; $i <= 30; $i++) {
            $correo = 'bot' . $i . '@bots.local';

            if (User::where('correo', $correo)->exists()) {
                continue;
            }

            User::create([
                'nombre'    => $faker->firstName(),
                'apellido1' => $faker->lastName(),
                'apellido2' => $faker->optional()->lastName(),
                'nick'      => $faker->unique()->userName(),
                'correo'    => $correo,
                'clave'     => Hash::make('bot_password_' . $i),
                'rol_corp'  => 'bot',
            ]);
        }
    }
}