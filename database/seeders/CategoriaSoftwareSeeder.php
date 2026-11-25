<?php

namespace Database\Seeders;

use App\Enums\CategoriasSoftware;
use App\Models\CategoriaSoftware;
use Illuminate\Database\Seeder;

/**
 * Carga el catálogo de categorías de software a partir de CategoriasSoftware.
 * Es idempotente: no pisa el nombre ni la descripción editados desde el panel.
 */
class CategoriaSoftwareSeeder extends Seeder
{
    public function run(): void
    {
        foreach (CategoriasSoftware::cases() as $categoria) {
            CategoriaSoftware::deEnum($categoria);
        }
    }
}
