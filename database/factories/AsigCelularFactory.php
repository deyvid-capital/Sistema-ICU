<?php

namespace Database\Factories;

use App\Models\AsigCelular;
use App\Models\Celular;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AsigCelular>
 */
class AsigCelularFactory extends Factory
{
    protected $model = AsigCelular::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'celular_id' => Celular::factory(),
            'usuario_id' => User::factory(),
            'fecha_ini' => fake()->date(),
            'fecha_fin' => null,
        ];
    }
}
