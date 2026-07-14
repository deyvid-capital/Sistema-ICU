<?php

namespace Database\Factories;

use App\Models\AsignacionEquipo;
use App\Models\Equipo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AsignacionEquipo>
 */
class AsignacionEquipoFactory extends Factory
{
    protected $model = AsignacionEquipo::class;

    public function definition(): array
    {
        return [
            'service_tag' => Equipo::query()->inRandomOrder()->value('service_tag') ?? Equipo::factory(),
            'usuario_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'fecha_inicio' => fake()->dateTimeBetween('-1 year', 'now'),
            'fecha_fin' => fake()->optional(0.3)->dateTimeBetween('now', '+1 year'),
            'motivo' => fake()->optional()->sentence(3),
            'observaciones' => fake()->optional()->sentence(),
        ];
    }
}
