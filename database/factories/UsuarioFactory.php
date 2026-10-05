<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Usuario>
 */
class UsuarioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre_usuario' => $this->faker->unique()->userName(),
            'contrasenia' => bcrypt('password'),
            'estado' => '1',
            'id_rol' => 2,
            'debe_cambiar_password' => 1,
            'bloqueado' => 0,
            'fecha_registro' => now(),
        ];
    }
}
