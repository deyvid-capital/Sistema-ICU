<?php

namespace Database\Factories;

use App\Models\AsigCargadorCel;
use App\Models\CargadorCel;
use App\Models\Celular;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AsigCargadorCel>
 */
class AsigCargadorCelFactory extends Factory
{
    protected $model = AsigCargadorCel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'celulares_id' => Celular::factory(),
            'cargador_serial' => CargadorCel::factory(),
            'motivo' => fake()->optional()->sentence(),
            'fecha' => fake()->date(),
        ];
    }
}
