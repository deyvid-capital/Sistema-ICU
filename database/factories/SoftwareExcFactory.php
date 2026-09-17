<?php

namespace Database\Factories;

use App\Enums\EstadoAprobacionExcepcion;
use App\Models\Area;
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
            'usuario_id' => User::factory(),
            'software_id' => Software::factory(),
            'area_aprobadora_id' => Area::factory(),
            'aprobador_id' => User::factory()->conRol('operador'),
            'tipo_excepcion' => fake()->randomElement(['Temporal', 'Permanente', 'Emergencia']),
            'motivo' => fake()->sentence(),
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'estado_aprobacion' => EstadoAprobacionExcepcion::Pendiente,
        ];
    }

    public function aprobada(): static
    {
        return $this->state(fn (): array => [
            'estado_aprobacion' => EstadoAprobacionExcepcion::Aprobada,
            'decidido_at' => now(),
        ]);
    }

    public function rechazada(): static
    {
        return $this->state(fn (): array => [
            'estado_aprobacion' => EstadoAprobacionExcepcion::Rechazada,
            'decidido_at' => now(),
            'comentario_decision' => fake()->sentence(),
        ]);
    }
}
