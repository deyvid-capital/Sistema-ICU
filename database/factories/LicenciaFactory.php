<?php

namespace Database\Factories;

use App\Models\Licencia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Licencia>
 */
class LicenciaFactory extends Factory
{
    protected $model = Licencia::class;

    public function definition(): array
    {
        return [
            'nombre_licencia' => fake()->unique()->bothify('Licencia-###-??'),
            'categoria' => fake()->randomElement(['MICROSOFT', 'ADOBE', 'ANTIVIRUS', 'VPN', 'OTRO']),
            'descripcion' => fake()->optional()->sentence(),
            'estado' => fake()->boolean(85),
        ];
    }
}
