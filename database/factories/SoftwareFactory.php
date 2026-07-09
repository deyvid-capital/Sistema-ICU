<?php

namespace Database\Factories;

use App\Models\Software;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Software>
 */
class SoftwareFactory extends Factory
{
    protected $model = Software::class;

    public function definition(): array
    {
        return [
            'categoria' => fake()->randomElement(['Productividad', 'Diseno', 'Seguridad', 'Comunicacion', 'Desarrollo']),
            'nombre_software' => fake()->unique()->words(2, true),
            'estado' => fake()->boolean(80),
            'fecha_autorizacion' => fake()->optional()->date(),
            'descripcion' => fake()->optional()->sentence(),
        ];
    }
}
