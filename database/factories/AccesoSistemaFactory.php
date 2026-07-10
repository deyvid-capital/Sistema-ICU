<?php

namespace Database\Factories;

use App\Models\AccesoSistema;
use App\Models\Sistema;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccesoSistema>
 */
class AccesoSistemaFactory extends Factory
{
    protected $model = AccesoSistema::class;

    public function definition(): array
    {
        return [
            'usuario_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'id_sistema' => Sistema::query()->inRandomOrder()->value('id_sistema') ?? Sistema::factory(),
            'usuario_sistema' => fake()->userName(),
            'nivel_acceso' => fake()->randomElement(['Operador', 'Supervisor', 'Administrador']),
            'estado' => fake()->boolean(80),
            'fecha_inicio' => fake()->date(),
            'fecha_fin' => fake()->optional()->date(),
            'observaciones' => fake()->optional()->sentence(),
        ];
    }
}
