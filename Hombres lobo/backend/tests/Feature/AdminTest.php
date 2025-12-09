<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class AdminTest extends TestCase
{
    use DatabaseMigrations;
    
    public function test_usuario_normal_no_accede_a_rutas_admin()
    {
        $user = User::factory()->create([
            'correo' => 'normal@test.com',
            'clave' => Hash::make('password'),
            'rol_corp' => 'usuario'
        ]);

        $token = $user->createToken('test', ['user'])->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->get('/api/admin/usuarios');

        $response->assertStatus(403);
    }

    public function test_admin_si_accede_a_rutas_admin()
    {
        $admin = User::factory()->create([
            'correo' => 'admin@test.com',
            'clave' => Hash::make('password'),
            'rol_corp' => 'admin'
        ]);

        $token = $admin->createToken('test', ['admin'])->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->get('/api/admin/usuarios');

        $response->assertStatus(200);
    }
}
