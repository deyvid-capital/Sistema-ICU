<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UsuarioFortitoken;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsuarioFortitoken>
 */
class UsuarioFortitokenFactory extends Factory
{
    protected $model = UsuarioFortitoken::class;

    public function definition(): array
    {
        return [
            'usuario_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'serial' => fake()->bothify('FTK-########'),
            'estado' => true,
        ];
    }
}
