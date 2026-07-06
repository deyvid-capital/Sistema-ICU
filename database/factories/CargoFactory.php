<?php

namespace Database\Factories;

use App\Models\Cargo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cargo>
 */
class CargoFactory extends Factory
{
    protected $model = Cargo::class;

    public function definition(): array
    {
        return [
            'cargo' => fake()->unique()->jobTitle(),
            'descripcion' => fake()->sentence(),
        ];
    }
}
