<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Cargo;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $areas = [
            ['area' => 'Recursos Humanos'],
            ['area' => 'Finanzas'],
            ['area' => 'Tecnología'],
            ['area' => 'Marketing'],
            ['area' => 'Operaciones'],
        ];

        // Insertar areas primero: los cargos dependen de un área existente.
        foreach ($areas as $area) {
            Area::create($area);
        }

        $cargos = [
            ['cargo' => 'Gerente', 'area_id' => 2], // Finanzas
            ['cargo' => 'Analista', 'area_id' => 1], // Recursos Humanos
            ['cargo' => 'Desarrollador', 'area_id' => 3], // Tecnología
            ['cargo' => 'Coordinador', 'area_id' => 4], // Marketing
            ['cargo' => 'Asistente', 'area_id' => 5], // Operaciones
        ];
        foreach ($cargos as $cargo) {
            Cargo::create($cargo);
        }

        $users = [
            [
                'name' => 'Ana López',
                'email' => 'ana.lopez@example.com',
                'password' => Hash::make('password'),
                'id_area' => 1, // Recursos Humanos
                'id_cargo' => 2, // Analista
            ],
            [
                'name' => 'Carlos Martínez',
                'email' => 'carlos.martinez@example.com',
                'password' => Hash::make('password'),
                'id_area' => 2, // Finanzas
                'id_cargo' => 1, // Gerente
            ],
            [
                'name' => 'Lucía García',
                'email' => 'lucia.garcia@example.com',
                'password' => Hash::make('password'),
                'id_area' => 3, // Tecnología
                'id_cargo' => 3, // Desarrollador
            ],
            [
                'name' => 'Jorge Rodríguez',
                'email' => 'jorge.rodriguez@example.com',
                'password' => Hash::make('password'),
                'id_area' => 4, // Marketing
                'id_cargo' => 4, // Coordinador
            ],
            [
                'name' => 'María González',
                'email' => 'maria.gonzalez@example.com',
                'password' => Hash::make('password'),
                'id_area' => 5, // Operaciones
                'id_cargo' => 5, // Asistente
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }

        $adminCargo = Cargo::query()->inRandomOrder()->first();

        User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('12345678'),
            'id_area' => $adminCargo->area_id,
            'id_cargo' => $adminCargo->id_cargo,
        ]);

        $this->call(SoftwareLicenciasSeeder::class);
        $this->call(AccesosSeeder::class);
    }
}
