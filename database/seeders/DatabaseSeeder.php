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
            ['email' => 'ti@capitalsafi.com'],
            [
                'name' => 'admin',
                'nombres' => 'Admin',
                'apellidos' => 'ICU',
                'password' => '12345678',
                'id_area' => $adminCargo?->area_id,
                'id_cargo' => $adminCargo?->id_cargo,
                'acceso_habilitado' => true,
            ],
        );

        // Roles/permisos y asignación de super_admin al admin (después de crearlo).
        $this->call(RolesPermissionsSeeder::class);

        $this->call(EquiposSeeder::class);
    }
}
