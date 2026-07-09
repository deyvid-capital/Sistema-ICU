<?php

namespace Database\Factories;

use App\Models\Software;
use App\Models\SoftwareUsuario;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SoftwareUsuario>
 */
class SoftwareUsuarioFactory extends Factory
{
    protected $model = SoftwareUsuario::class;

    public function definition(): array
    {
        return [
            'usuario_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'id_software' => Software::query()->inRandomOrder()->value('id_software') ?? Software::factory(),
            'estado' => fake()->boolean(80),
        ];
    }
}
