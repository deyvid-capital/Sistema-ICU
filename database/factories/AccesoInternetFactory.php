<?php

namespace Database\Factories;

use App\Models\AccesoInternet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccesoInternet>
 */
class AccesoInternetFactory extends Factory
{
    protected $model = AccesoInternet::class;

    public function definition(): array
    {
        return [
            'usuario_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'usuario_red' => fake()->userName().'.red',
            'nivel_acceso' => fake()->randomElement(['Básico', 'Estándar', 'Avanzado']),
            'estado' => fake()->boolean(80),
            'fecha' => fake()->optional()->date(),
            'observaciones' => fake()->optional()->sentence(),
        ];
    }
}
