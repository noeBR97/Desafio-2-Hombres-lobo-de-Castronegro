<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->firstName(),
            'apellido1' => $this->faker->lastName(),
            'apellido2' => $this->faker->optional()->lastName(),
            'nick' => $this->faker->unique()->userName(),
            'correo' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'clave' => Hash::make('secret'), //contraseña por defecto
            'rol_corp' => 'usuario',
            'remember_token' => Str::random(10),
            'avatar_url' => null,
            'avatar_predefinido' => 'avatar-lobo.png'
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
