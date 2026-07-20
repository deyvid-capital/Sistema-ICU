<?php

namespace Database\Factories;

use App\Models\Equipo;
use App\Models\NotaSalidaEquipo;
use App\Models\UsuarioExterno;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotaSalidaEquipo>
 */
class NotaSalidaEquipoFactory extends Factory
{
    protected $model = NotaSalidaEquipo::class;

    public function definition(): array
    {
        return [
            'equipo_id' => Equipo::factory(),
            'usuario_externo_id' => UsuarioExterno::factory(),
            'numero' => fake()->unique()->bothify('NS-####'),
            'fecha' => fake()->date(),
            'nota' => fake()->optional()->sentence(),
        ];
    }
}
