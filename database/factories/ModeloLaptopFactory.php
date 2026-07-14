<?php

namespace Database\Factories;

use App\Models\ModeloLaptop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModeloLaptop>
 */
class ModeloLaptopFactory extends Factory
{
    protected $model = ModeloLaptop::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->randomElement([
                'Latitude 5540',
                'Latitude 7440',
                'Precision 5680',
                'ThinkPad T14',
                'ThinkPad X1 Carbon',
                'EliteBook 840',
                'ProBook 450',
                'Surface Laptop 5',
            ]).' '.fake()->unique()->numerify('##'),
        ];
    }
}
