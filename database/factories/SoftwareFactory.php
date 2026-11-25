<?php

namespace Database\Factories;

use App\Enums\CategoriasSoftware;
use App\Models\CategoriaSoftware;
use App\Models\Software;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Software>
 */
class SoftwareFactory extends Factory
{
    protected $model = Software::class;

    public function definition(): array
    {
        return [
            'categoria_id' => CategoriaSoftware::factory(),
            'nombre_software' => fake()->unique()->words(2, true),
            'estado' => fake()->boolean(80),
            'fecha_autorizacion' => fake()->optional()->date(),
            'descripcion' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Software de la categoría del catálogo que corresponde al caso del enum.
     */
    public function deCategoria(CategoriasSoftware $categoria): static
    {
        return $this->state(fn (): array => [
            'categoria_id' => CategoriaSoftware::deEnum($categoria)->getKey(),
        ]);
    }
}
