<?php

namespace Database\Factories;

use App\Models\Licencia;
use App\Models\LicenciaUsuario;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LicenciaUsuario>
 */
class LicenciaUsuarioFactory extends Factory
{
    protected $model = LicenciaUsuario::class;

    public function definition(): array
    {
        return [
            'usuario_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'licencia_id' => Licencia::query()->inRandomOrder()->value('id') ?? Licencia::factory(),
            'fecha' => fake()->optional()->date(),
            'estado' => fake()->boolean(80),
        ];
    }
}
