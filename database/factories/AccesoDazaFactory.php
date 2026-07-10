<?php

namespace Database\Factories;

use App\Models\AccesoDaza;
use App\Models\Fondo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccesoDaza>
 */
class AccesoDazaFactory extends Factory
{
    protected $model = AccesoDaza::class;

    public function definition(): array
    {
        return [
            'usuario_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'usuario_daza' => fake()->userName(),
            'nivel_acceso' => fake()->randomElement(['Lectura', 'Escritura', 'Admin']),
            'fondo_id' => Fondo::query()->inRandomOrder()->value('id_fondo') ?? Fondo::factory(),
            'ambiente' => fake()->randomElement(['Produccion', 'Pruebas', 'Desarrollo']),
            'estado' => fake()->boolean(80),
            'fecha' => fake()->optional()->date(),
        ];
    }
}
