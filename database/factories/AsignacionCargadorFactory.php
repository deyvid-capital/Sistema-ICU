<?php

namespace Database\Factories;

use App\Models\AsignacionCargador;
use App\Models\Cargador;
use App\Models\Equipo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AsignacionCargador>
 */
class AsignacionCargadorFactory extends Factory
{
    protected $model = AsignacionCargador::class;

    public function definition(): array
    {
        return [
            'serial_cargador' => Cargador::query()->inRandomOrder()->value('serial') ?? Cargador::factory(),
            'service_tag' => Equipo::query()->inRandomOrder()->value('service_tag') ?? Equipo::factory(),
            'fecha_inicio' => fake()->dateTimeBetween('-1 year', 'now'),
            'fecha_fin' => fake()->optional(0.3)->dateTimeBetween('now', '+1 year'),
        ];
    }
}
