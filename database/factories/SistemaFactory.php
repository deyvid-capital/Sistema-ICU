<?php

namespace Database\Factories;

use App\Models\Sistema;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sistema>
 */
class SistemaFactory extends Factory
{
    protected $model = Sistema::class;

    public function definition(): array
    {
        return [
            'nombre_sistema' => fake()->unique()->words(2, true),
            'descripcion' => fake()->optional()->sentence(),
        ];
    }
}
