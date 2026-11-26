<?php

namespace Database\Factories;

use App\Models\PersonalExternoSoporteTecnico;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PersonalExternoSoporteTecnico>
 */
class PersonalExternoSoporteTecnicoFactory extends Factory
{
    protected $model = PersonalExternoSoporteTecnico::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'carnet' => fake()->unique()->numerify('########'),
            'empresa' => fake()->optional()->company(),
            'cargo' => fake()->jobTitle(),
        ];
    }
}
