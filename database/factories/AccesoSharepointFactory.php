<?php

namespace Database\Factories;

use App\Models\AccesoSharepoint;
use App\Models\SitioSharepoint;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccesoSharepoint>
 */
class AccesoSharepointFactory extends Factory
{
    protected $model = AccesoSharepoint::class;

    public function definition(): array
    {
        return [
            'usuario_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'sitio_id' => SitioSharepoint::query()->inRandomOrder()->value('id') ?? SitioSharepoint::factory(),
            'estado' => fake()->boolean(80),
        ];
    }
}
