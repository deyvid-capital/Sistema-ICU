<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Area;
use App\Models\Cargo;
use Illuminate\Support\Facades\Hash;
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
        // User::factory(10)->create();

        Area::factory()->count(10)->create();
        Cargo::factory()->count(10)->create();

        User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('12345678'),
            'id_area' => rand(1, 10),
            'id_cargo' => rand(1, 10),
        ]);
    }
}
