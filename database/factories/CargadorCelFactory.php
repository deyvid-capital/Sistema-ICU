<?php

namespace Database\Factories;

use App\Models\CargadorCel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CargadorCel>
 */
class CargadorCelFactory extends Factory
{
    protected $model = CargadorCel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'serial' => strtoupper(fake()->unique()->bothify('CCEL-####??')),
            'modelo' => fake()->words(2, true),
            'observacion' => fake()->optional()->sentence(),
        ];
    }
}
