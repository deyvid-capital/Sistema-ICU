<?php

namespace Database\Factories;

use App\Models\RecursoVpn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecursoVpn>
 */
class RecursoVpnFactory extends Factory
{
    protected $model = RecursoVpn::class;

    public function definition(): array
    {
        return [
            'nombre_vpn' => 'VPN '.fake()->words(2, true),
            'descripcion' => fake()->optional()->sentence(),
            'estado' => fake()->boolean(80),
        ];
    }
}
