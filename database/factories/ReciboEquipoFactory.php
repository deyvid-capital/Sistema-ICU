<?php

namespace Database\Factories;

use App\Models\Equipo;
use App\Models\ReciboEquipo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReciboEquipo>
 */
class ReciboEquipoFactory extends Factory
{
    protected $model = ReciboEquipo::class;

    public function definition(): array
    {
        return [
            'equipo_id' => Equipo::query()->inRandomOrder()->value('service_tag') ?? Equipo::factory(),
            'fecha' => fake()->dateTimeBetween('-1 year', 'now'),
            'url' => fake()->optional()->url(),
            'observaciones' => fake()->optional()->sentence(),
        ];
    }
}
