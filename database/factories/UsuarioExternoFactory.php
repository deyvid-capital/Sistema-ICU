<?php

namespace Database\Factories;

use App\Models\UsuarioExterno;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsuarioExterno>
 */
class UsuarioExternoFactory extends Factory
{
    protected $model = UsuarioExterno::class;

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
