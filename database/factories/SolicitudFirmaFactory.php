<?php

namespace Database\Factories;

use App\Enums\EstadoSolicitudFirma;
use App\Models\SolicitudFirma;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SolicitudFirma>
 */
class SolicitudFirmaFactory extends Factory
{
    protected $model = SolicitudFirma::class;

    public function definition(): array
    {
        return [
            'codigo' => 'SF-'.now()->year.'-'.fake()->unique()->numerify('#####'),
            'asunto' => fake()->sentence(4),
            'mensaje' => fake()->optional()->sentence(),
            'orden_obligatorio' => false,
            'estado' => EstadoSolicitudFirma::Borrador,
            'fecha_limite' => now()->addDays(3),
            'recordatorio_cada_horas' => 24,
            'creado_por' => User::factory(),
        ];
    }

    public function ordenObligatorio(): static
    {
        return $this->state(fn (): array => ['orden_obligatorio' => true]);
    }

    public function enCurso(): static
    {
        return $this->state(fn (): array => [
            'estado' => EstadoSolicitudFirma::EnCurso,
            'enviada_at' => now(),
        ]);
    }
}
