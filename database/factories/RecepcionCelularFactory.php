<?php

namespace Database\Factories;

use App\Models\AsigCelular;
use App\Models\RecepcionCelular;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecepcionCelular>
 */
class RecepcionCelularFactory extends Factory
{
    protected $model = RecepcionCelular::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asignacion_cel_id' => AsigCelular::factory(),
            'numero' => fake()->unique()->numerify('###-2026'),
            'fecha' => fake()->date(),
            'nota' => fake()->optional()->sentence(),
        ];
    }
}
