<?php

namespace Database\Factories;

use App\Models\Microprocesador;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Microprocesador>
 */
class MicroprocesadorFactory extends Factory
{
    protected $model = Microprocesador::class;

    public function definition(): array
    {
        return [
            'nombre_micro' => fake()->unique()->randomElement([
                'Intel Core i5-1335U',
                'Intel Core i7-1365U',
                'Intel Core i7-13700H',
                'AMD Ryzen 5 7530U',
                'AMD Ryzen 7 7730U',
                'Intel Core Ultra 7 155H',
            ]).' '.fake()->unique()->numerify('#'),
        ];
    }
}
