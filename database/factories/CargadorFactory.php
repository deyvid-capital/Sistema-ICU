<?php

namespace Database\Factories;

use App\Models\Cargador;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cargador>
 */
class CargadorFactory extends Factory
{
    protected $model = Cargador::class;

    public function definition(): array
    {
        return [
            'serial' => strtoupper(fake()->unique()->bothify('CHG-????####')),
            'marca' => fake()->randomElement(['Dell', 'HP', 'Lenovo', 'Apple', 'Generic']),
            'modelo' => fake()->bothify('AC-##W'),
            'estado' => fake()->randomElement(['disponible', 'asignado', 'danado', 'baja']),
            'observaciones' => fake()->optional()->sentence(),
        ];
    }
}
