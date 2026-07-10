<?php

namespace Database\Factories;

use App\Models\SitioSharepoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SitioSharepoint>
 */
class SitioSharepointFactory extends Factory
{
    protected $model = SitioSharepoint::class;

    public function definition(): array
    {
        return [
            'nombre_sitio' => fake()->words(3, true),
            'url' => fake()->url(),
            'descripcion' => fake()->optional()->sentence(),
            'estado' => fake()->boolean(80),
        ];
    }
}
