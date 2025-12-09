<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::create([
            'nombre'   => 'Test',
            'apellido1'=> 'User',
            'nick'     => 'testuser',
            'correo'   => 'test@example.com',
            'clave'    => Hash::make('password'),
            'rol_corp' => 'usuario',
        ]);

        $this->call(BotUsersSeeder::class);
    }
}