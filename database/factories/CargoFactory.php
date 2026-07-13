<?php

namespace Database\Factories;

use App\Models\Area;
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
            'area_id' => Area::query()->inRandomOrder()->value('id_area') ?? Area::factory(),
            'cargo' => fake()->unique()->jobTitle(),
            'descripcion' => fake()->sentence(),
        ];
    }
}
