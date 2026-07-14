<?php

namespace Database\Factories;

use App\Models\Equipo;
use App\Models\MantenimientoEquipo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MantenimientoEquipo>
 */
class MantenimientoEquipoFactory extends Factory
{
    protected $model = MantenimientoEquipo::class;

    public function definition(): array
    {
        return [
            'service_tag' => Equipo::query()->inRandomOrder()->value('service_tag') ?? Equipo::factory(),
            'fecha_soporte' => fake()->dateTimeBetween('-1 year', 'now'),
            'cap_antes' => fake()->optional()->sentence(4),
            'cap_despues' => fake()->optional()->sentence(4),
            'observaciones' => fake()->optional()->sentence(),
        ];
    }
}
