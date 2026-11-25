<?php

namespace Database\Factories;

use App\Models\UsuarioExternoSoporteEquipo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsuarioExternoSoporteEquipo>
 */
class UsuarioExternoSoporteEquipoFactory extends Factory
{
    protected $model = UsuarioExternoSoporteEquipo::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'carnet' => fake()->unique()->numerify('########'),
            'empresa' => fake()->optional()->company(),
            'cargo' => fake()->jobTitle(),
        ];
    }
}
