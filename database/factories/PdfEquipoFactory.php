<?php

namespace Database\Factories;

use App\Models\EntregaEquipo;
use App\Models\PdfEquipo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PdfEquipo>
 */
class PdfEquipoFactory extends Factory
{
    protected $model = PdfEquipo::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->words(2, true),
            'documento_id' => EntregaEquipo::factory(),
            'documento_type' => EntregaEquipo::class,
            'fecha' => fake()->date(),
            'url' => 'pdf-equipos/'.fake()->uuid().'.pdf',
        ];
    }
}
