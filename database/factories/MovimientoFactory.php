<?php

namespace Database\Factories;

use App\Models\Cargo;
use App\Models\Movimiento;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Movimiento>
 */
class MovimientoFactory extends Factory
{
    protected $model = Movimiento::class;

    public function definition(): array
    {
        return [
            'usuario_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'tipo' => 'Alta',
            'fecha' => now()->startOfDay(),
            'cargo_id' => Cargo::query()->inRandomOrder()->value('id_cargo') ?? Cargo::factory(),
        ];
    }

    public function alta(): static
    {
        return $this->state(fn (): array => ['tipo' => 'Alta']);
    }

    /**
     * Ojo: crear un movimiento de Baja dispara MovimientoObserver y por tanto
     * la cascada de desactivación de accesos y asignaciones del colaborador.
     */
    public function baja(): static
    {
        return $this->state(fn (): array => ['tipo' => 'Baja']);
    }
}
