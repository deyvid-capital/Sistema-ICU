<?php

namespace Database\Factories;

use App\Models\MemoriaRam;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemoriaRam>
 */
class MemoriaRamFactory extends Factory
{
    protected $model = MemoriaRam::class;

    public function definition(): array
    {
        return [
            'tipo_ram' => fake()->unique()->randomElement([
                '8GB DDR4',
                '16GB DDR4',
                '32GB DDR4',
                '16GB DDR5',
                '32GB DDR5',
                '64GB DDR5',
            ]),
        ];
    }
}
