<?php

namespace Database\Factories;

use App\Models\AzureAd;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AzureAd>
 */
class AzureAdFactory extends Factory
{
    protected $model = AzureAd::class;

    public function definition(): array
    {
        return [
            'usuario_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'iniciales' => fake()->lexify('???'),
            'sincronizacion' => fake()->randomElement(['Sincronizado', 'Pendiente', 'Error']),
            'mfa' => fake()->boolean(70),
            'estado' => fake()->boolean(80),
        ];
    }
}
