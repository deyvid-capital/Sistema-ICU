<?php

namespace Database\Factories;

use App\Models\Cargo;
use App\Models\Software;
use App\Models\SoftwareCargo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SoftwareCargo>
 */
class SoftwareCargoFactory extends Factory
{
    protected $model = SoftwareCargo::class;

    public function definition(): array
    {
        return [
            'software_id' => Software::query()->inRandomOrder()->value('id_software') ?? Software::factory(),
            'cargo_id' => Cargo::query()->inRandomOrder()->value('id_cargo') ?? Cargo::factory(),
        ];
    }
}
