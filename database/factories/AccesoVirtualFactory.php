<?php

namespace Database\Factories;

use App\Models\AccesoInternet;
use App\Models\AccesoVirtual;
use App\Models\RecursoVpn;
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
            'internet_id' => AccesoInternet::query()->inRandomOrder()->value('id') ?? AccesoInternet::factory(),
            'virtual_id' => Virtual::query()->inRandomOrder()->value('id') ?? Virtual::factory(),
            'vpn_id' => RecursoVpn::query()->inRandomOrder()->value('id') ?? RecursoVpn::factory(),
            'estado' => true,
        ];
    }
}
