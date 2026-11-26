<?php

namespace Database\Factories;

use App\Models\Equipo;
use App\Models\NotaRecepcionEquipo;
use App\Models\PersonalExternoSoporteTecnico;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotaRecepcionEquipo>
 */
class NotaRecepcionEquipoFactory extends Factory
{
    protected $model = NotaRecepcionEquipo::class;

    public function definition(): array
    {
        return [
            'equipo_id' => Equipo::factory(),
            'personal_externo_soporte_id' => PersonalExternoSoporteTecnico::factory(),
            'numero' => sprintf('%03d-%d', fake()->unique()->numberBetween(1, 99999), (int) now()->format('Y')),
            'fecha' => fake()->date(),
            'nota' => fake()->optional()->sentence(),
        ];
    }
}
