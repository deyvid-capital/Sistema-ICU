<?php

namespace Database\Factories;

use App\Models\AsignacionEquipo;
use App\Models\RecepcionEquipo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecepcionEquipo>
 */
class RecepcionEquipoFactory extends Factory
{
    protected $model = RecepcionEquipo::class;

    public function definition(): array
    {
        return [
            'asignacion_id' => AsignacionEquipo::factory(),
            'numero' => sprintf('%03d-%d', fake()->unique()->numberBetween(1, 99999), (int) now()->format('Y')),
            'fecha' => fake()->date(),
            'observaciones' => fake()->optional()->sentence(),
        ];
    }
}
