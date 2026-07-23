<?php

namespace Database\Factories;

use App\Models\EntregaCelular;
use App\Models\PdfCelular;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PdfCelular>
 */
class PdfCelularFactory extends Factory
{
    protected $model = PdfCelular::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Formulario de entrega de celular',
            'documento_id' => EntregaCelular::factory(),
            'documento_type' => EntregaCelular::class,
            'fecha' => fake()->date(),
            'url' => 'pdf-celulares/entrega/FUC-'.fake()->numerify('###-2026').'.pdf',
        ];
    }
}
