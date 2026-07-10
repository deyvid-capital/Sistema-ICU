<?php

namespace Database\Factories;

use App\Models\Fondo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fondo>
 */
class FondoFactory extends Factory
{
    protected $model = Fondo::class;

    public function definition(): array
    {
        return [
            'codigo' => strtoupper(fake()->unique()->bothify('F###')),
            'nombre_completo' => fake()->company(),
            'descripcion' => fake()->optional()->sentence(),
        ];
    }
}
