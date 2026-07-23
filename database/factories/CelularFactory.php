<?php

namespace Database\Factories;

use App\Models\Celular;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Celular>
 */
class CelularFactory extends Factory
{
    protected $model = Celular::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => strtoupper(fake()->unique()->bothify('CEL-####??')),
            'fecha_compra' => fake()->optional()->date(),
            'precio' => fake()->optional()->numberBetween(500, 5000),
            'marca' => fake()->randomElement(['Apple', 'Samsung', 'Xiaomi', 'Motorola']),
            'modelo' => fake()->words(2, true),
            'imei' => fake()->unique()->numerify('###############'),
            'procesador' => fake()->randomElement(['A16 Bionic', 'Snapdragon 8 Gen 2', 'Dimensity 9200']),
            'disco' => fake()->randomElement(['128 GB', '256 GB', '512 GB']),
            'ram' => fake()->randomElement(['6 GB', '8 GB', '12 GB']),
            'bateria' => fake()->randomElement(['4000 mAh', '4500 mAh', '5000 mAh']),
            'color' => fake()->safeColorName(),
        ];
    }
}
