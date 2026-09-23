<?php

namespace Database\Factories;

use App\Models\AccesoVirtual;
use App\Models\RecursoVpn;
use App\Models\User;
use App\Models\Virtual;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccesoVirtual>
 */
class AccesoVirtualFactory extends Factory
{
    protected $model = AccesoVirtual::class;

    public function definition(): array
    {
        return [
            'usuario_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'virtual_id' => Virtual::query()->inRandomOrder()->value('id') ?? Virtual::factory(),
            'vpn_id' => RecursoVpn::query()->inRandomOrder()->value('id') ?? RecursoVpn::factory(),
            'estado' => true,
        ];
    }

    public function sinVpn(): static
    {
        return $this->state(fn (array $attributes): array => [
            'vpn_id' => null,
        ]);
    }
}
