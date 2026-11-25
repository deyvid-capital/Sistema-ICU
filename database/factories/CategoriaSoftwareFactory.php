<?php

namespace Database\Factories;

use App\Models\CategoriaSoftware;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CategoriaSoftware>
 */
class CategoriaSoftwareFactory extends Factory
{
    protected $model = CategoriaSoftware::class;

    public function definition(): array
    {
        $nombre = fake()->unique()->words(2, true);

        return [
            'codigo' => Str::slug($nombre, '_'),
            'nombre' => Str::ucfirst($nombre),
            'descripcion' => fake()->optional()->sentence(),
        ];
    }
}
