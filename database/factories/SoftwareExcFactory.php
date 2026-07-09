<?php

namespace Database\Factories;

use App\Models\Software;
use App\Models\SoftwareExc;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SoftwareExc>
 */
class SoftwareExcFactory extends Factory
{
    protected $model = SoftwareExc::class;

    public function definition(): array
    {
        $fechaInicio = fake()->dateTimeBetween('-6 months', 'now');
        $fechaFin = fake()->boolean(60) ? fake()->dateTimeBetween($fechaInicio, '+3 months') : null;

        return [
            'usuario_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'software_id' => Software::query()->inRandomOrder()->value('id_software') ?? Software::factory(),
            'tipo_excepcion' => fake()->randomElement(['Temporal', 'Permanente', 'Emergencia']),
            'motivo' => fake()->sentence(),
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
        ];
    }
}
