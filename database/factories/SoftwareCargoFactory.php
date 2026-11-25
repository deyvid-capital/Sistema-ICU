<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Software;
use App\Models\SoftwareArea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SoftwareArea>
 */
class SoftwareAreaFactory extends Factory
{
    protected $model = SoftwareArea::class;

    public function definition(): array
    {
        return [
            'software_id' => Software::query()->inRandomOrder()->value('id_software') ?? Software::factory(),
            'area_id' => Area::query()->inRandomOrder()->value('id_area') ?? Area::factory(),
        ];
    }
}
