<?php

namespace Database\Factories;

use App\Models\Equipo;
use App\Models\MemoriaRam;
use App\Models\Microprocesador;
use App\Models\ModeloLaptop;
use App\Models\TarjetaVideo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Equipo>
 */
class EquipoFactory extends Factory
{
    protected $model = Equipo::class;

    public function definition(): array
    {
        return [
            'service_tag' => strtoupper(fake()->unique()->bothify('??######')),
            'modelo_id' => ModeloLaptop::query()->inRandomOrder()->value('id') ?? ModeloLaptop::factory(),
            'microprocesador_id' => Microprocesador::query()->inRandomOrder()->value('id') ?? Microprocesador::factory(),
            'disco_duro' => fake()->randomElement(['256GB SSD', '512GB SSD', '1TB SSD', '1TB HDD + 256GB SSD']),
            'ram_id' => MemoriaRam::query()->inRandomOrder()->value('id') ?? MemoriaRam::factory(),
            'gpu_id' => TarjetaVideo::query()->inRandomOrder()->value('id') ?? TarjetaVideo::factory(),
            'proveedor' => fake()->optional()->company(),
            'inicio_garantia' => fake()->optional()->dateTimeBetween('-2 years', 'now'),
            'venc_garantia' => fake()->optional()->dateTimeBetween('now', '+2 years'),
            'nombre_equipo' => fake()->randomElement(['BOCS', 'LAPTOP', 'WORKSTATION']).'-'.fake()->numerify('###'),
            'bloqueo_bios' => fake()->boolean(80),
            'codigo_activo' => fake()->optional()->bothify('ACT-####'),
            'so' => fake()->randomElement(['Windows 11 PRO', 'Windows 10 PRO', 'Ubuntu 24.04']),
            'licencia_so' => fake()->optional()->bothify('XXXXX-XXXXX-XXXXX-XXXXX-XXXXX'),
            'etiquetado' => fake()->boolean(70),
            'usuario_admin' => fake()->boolean(40),
            'litigation_hold' => false,
            'bitlocker' => false,
            'estado' => fake()->randomElement(['sin asignar', 'asignado', 'en mantenimiento', 'resguardo', 'baja']),
            'observaciones' => fake()->optional()->randomElement([
                '<p><strong>Equipo revisado</strong> y listo para inventario.</p>',
                '<p>Observación: <em>requiere limpieza</em>.</p>',
                null,
            ]),
        ];
    }
}
