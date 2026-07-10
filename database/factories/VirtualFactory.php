<?php

namespace Database\Factories;

use App\Models\Virtual;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Virtual>
 */
class VirtualFactory extends Factory
{
    protected $model = Virtual::class;

    public function definition(): array
    {
        return [
            'maquina_virtual' => 'VM-'.fake()->unique()->bothify('??-##'),
            'estado' => fake()->boolean(80),
        ];
    }
}
