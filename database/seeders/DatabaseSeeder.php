<?php

namespace Database\Seeders;

use App\Models\Cargo;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CatalogoPersonalSeeder::class);

        $adminCargo = Cargo::query()->where('cargo', 'Pasante IT')->first()
            ?? Cargo::query()->first();

        User::query()->firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'admin',
                'nombres' => 'Admin',
                'apellidos' => 'ICU',
                'password' => '12345678',
                'id_area' => $adminCargo?->area_id,
                'id_cargo' => $adminCargo?->id_cargo,
            ],
        );

        $this->call([
            SoftwareLicenciasSeeder::class,
            AccesosSeeder::class,
            EquiposSeeder::class,
            CelularesSeeder::class,
        ]);
    }
}
