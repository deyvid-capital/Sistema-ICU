<?php

namespace Database\Factories;

use App\Models\AsignacionEquipo;
use App\Models\EntregaEquipo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EntregaEquipo>
 */
class EntregaEquipoFactory extends Factory
{
    protected $model = EntregaEquipo::class;

    public function definition(): array
    {
        return [
            'asignacion_id' => AsignacionEquipo::factory(),
            'numero' => sprintf('%03d-%d', fake()->unique()->numberBetween(1, 99999), (int) now()->format('Y')),
            'fecha' => fake()->date(),
            'mouse' => fake()->boolean(),
            'cooler' => fake()->boolean(),
            'audifonos' => fake()->boolean(),
            'observaciones' => fake()->optional()->sentence(),
            'nota' => fake()->optional()->sentence(),
        ];
    }
}
