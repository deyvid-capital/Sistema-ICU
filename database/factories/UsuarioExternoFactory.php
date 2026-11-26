<?php

namespace Database\Factories;

use App\Enums\PermisoDocumentoCompartido;
use App\Models\User;
use App\Models\UsuarioExterno;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsuarioExterno>
 */
class UsuarioExternoFactory extends Factory
{
    protected $model = UsuarioExterno::class;

    public function definition(): array
    {
        $inicio = fake()->dateTimeBetween('-1 month', 'now');

        return [
            'nombre_contacto' => fake()->name(),
            'empresa' => fake()->company(),
            'cargo' => fake()->jobTitle(),
            'correo' => fake()->unique()->safeEmail(),
            'responsable_id' => User::factory(),
            'sitio_sharepoint_id' => null,
            'onedrive' => null,
            'fecha_inicio' => $inicio,
            'fecha_fin' => fake()->dateTimeBetween('+1 week', '+2 months'),
            'motivo' => fake()->randomElement(['Auditoría', 'Consultoría', 'Revisión contable']),
            'estado' => true,
            'permiso' => fake()->randomElement(PermisoDocumentoCompartido::cases()),
        ];
    }

    public function inactiva(): static
    {
        return $this->state(fn (): array => ['estado' => false]);
    }
}
