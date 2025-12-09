<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class AuthTest extends TestCase
{
    use DatabaseMigrations;
    
    public function test_login_correcto() {
        $user = User::factory()->create([
            'correo' => 'admin@test.com',
            'clave' => Hash::make('12345678'),
            'rol_corp' => 'admin'
        ]);

        $response = $this->post('/api/login', [
            'correo' => 'admin@test.com',
            'clave' => '12345678'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'ok',
            'token',
            'user' => ['id', 'nick', 'correo', 'rol_corp']
        ]);

        $this->assertStringContainsString('admin', $response['user']['rol_corp']);
    }

    public function test_login_clave_incorrecta() {
        $user = User::factory()->create([
            'correo' => 'fallo@test.com',
            'clave' => Hash::make('correcta'),
        ]);

        $response = $this->post('/api/login', [
            'correo' => 'fallo@test.com',
            'clave' => 'incorrecta'
        ]);

        $response->assertStatus(401);
    }
}
