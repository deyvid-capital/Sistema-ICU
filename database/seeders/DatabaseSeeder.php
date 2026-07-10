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

        $cargos = [
            ['cargo' => 'Gerente'],
            ['cargo' => 'Analista'],
            ['cargo' => 'Desarrollador'],
            ['cargo' => 'Coordinador'],
            ['cargo' => 'Asistente'],
        ];
        // Insertar cargos
        foreach ($cargos as $cargo) {
            Cargo::create($cargo);
        }

        // Insertar areas
        foreach ($areas as $area) {
            Area::create($area);
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

        User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('12345678'),
            'id_area' => rand(1, 5),
            'id_cargo' => rand(1, 5),
        ]);

        $this->call(SoftwareLicenciasSeeder::class);
        $this->call(AccesosSeeder::class);
    }
}
