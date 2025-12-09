<?php
namespace Database\Seeders;

use App\Models\User;
use App\Models\Partida;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $usuariosGenerados = [];

        /**
         * 1) Crear usuarios "humanos" solo si no hay ninguno
         */
        $yaHayHumanos = User::where('rol_corp', '!=', 'bot')->exists();

        if (!$yaHayHumanos) {
            // Crear 10 usuarios "normales" aleatorios SOLO la primera vez
            $usuarios = User::factory()->count(10)->make();

            foreach ($usuarios as $index => $userData) {
                $passwordPlano = "password" . ($index + 1);

                $user = User::create([
                    'nombre'             => $userData->nombre,
                    'apellido1'          => $userData->apellido1,
                    'apellido2'          => $userData->apellido2,
                    'nick'               => $userData->nick,
                    'correo'             => $userData->correo,
                    'clave'              => Hash::make($passwordPlano),
                    'rol_corp'           => $userData->rol_corp,
                    'avatar_predefinido' => 'avatar-lobo.png',
                ]);

                $usuariosGenerados[] = [
                    'id'       => $user->id,
                    'correo'   => $user->correo,
                    'password' => $passwordPlano,
                    'rol'      => $user->rol_corp,
                ];
            }

            // Guardar el archivo con usuarios generados (solo la primera vez)
            $this->guardarArchivoUsuarios($usuariosGenerados);
        }

        /**
         * 2) Asegurar BOTS (este seeder ya es idempotente)
         */
        $this->call([
            BotUsersSeeder::class,
        ]);

        /**
         * 3) Crear partidas de prueba solo si no hay ninguna
         */
        if (Partida::count() === 0) {
            $creadoresPartidas = User::where('rol_corp', '!=', 'bot')
                ->inRandomOrder()
                ->take(4)
                ->get();

            foreach ($creadoresPartidas as $usuario) {
                Partida::factory()->create([
                    'id_creador_partida' => $usuario->id,
                ]);
            }
        }
    }

    private function guardarArchivoUsuarios($data)
    {
        $path = storage_path('app/usuarios_generados.txt');

        $contenido = "=== Usuarios generados " . now() . " ===\n\n";

        foreach ($data as $u) {
            $contenido .= "ID: {$u['id']}\n";
            $contenido .= "Correo: {$u['correo']}\n";
            $contenido .= "Contraseña: {$u['password']}\n";
            $contenido .= "Rol: {$u['rol']}\n";
            $contenido .= "--------------------------------------\n";
        }

        File::put($path, $contenido);
    }
}