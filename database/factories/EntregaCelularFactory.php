<?php

namespace Database\Factories;

use App\Models\AsigCelular;
use App\Models\EntregaCelular;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EntregaCelular>
 */
class EntregaCelularFactory extends Factory
{
    protected $model = EntregaCelular::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asignacion_cel_id' => AsigCelular::factory(),
            'numero' => fake()->unique()->numerify('###-2026'),
            'fecha' => fake()->date(),
            'nota' => fake()->optional()->paragraph(),
            'observaciones' => fake()->optional()->sentence(),
        ];
    }
}
