<?php

namespace Database\Factories;

use App\Models\AccUdCompartida;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccUdCompartida>
 */
class AccUdCompartidaFactory extends Factory
{
    protected $model = AccUdCompartida::class;

    public function definition(): array
    {
        return [
            'usuario_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'unidad_compartida' => '\\\\'.fake()->domainWord().'\\'.fake()->word(),
            'estado' => fake()->boolean(80),
        ];
    }
}
