<?php

namespace Database\Factories;

use App\Models\TarjetaVideo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TarjetaVideo>
 */
class TarjetaVideoFactory extends Factory
{
    protected $model = TarjetaVideo::class;

    public function definition(): array
    {
        return [
            'nombre_gpu' => fake()->unique()->randomElement([
                'Intel Iris Xe',
                'Intel Arc Graphics',
                'NVIDIA RTX 4050',
                'NVIDIA RTX 4060',
                'AMD Radeon 780M',
                'Integrada',
            ]).' '.fake()->unique()->numerify('#'),
        ];
    }
}
