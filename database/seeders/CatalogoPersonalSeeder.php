<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Cargo;
use App\Models\User;
use Database\Seeders\Concerns\ReadsCsvData;
use Illuminate\Database\Seeder;

class CatalogoPersonalSeeder extends Seeder
{
    use ReadsCsvData;

    public function run(): void
    {
        $areas = $this->rowsFromCsv('areas.csv');
        $cargos = $this->rowsFromCsv('cargos.csv');
        $usuarios = $this->rowsFromCsv('usuarios.csv');

        foreach ($areas as $area) {
            Area::query()->updateOrCreate(
                ['area' => $area['area']],
                ['area' => $area['area']],
            );
        }

        $areasPorNombre = Area::query()->pluck('id_area', 'area');
        $areaIdPorCargo = [];

        foreach ($usuarios as $usuario) {
            $cargo = $usuario['cargo'] ?? null;
            $area = $usuario['area'] ?? null;

            if (blank($cargo) || blank($area) || isset($areaIdPorCargo[$cargo])) {
                continue;
            }

            $areaIdPorCargo[$cargo] = $areasPorNombre[$area] ?? null;
        }

        foreach ($cargos as $cargo) {
            Cargo::query()->updateOrCreate(
                ['cargo' => $cargo['cargo']],
                [
                    'cargo' => $cargo['cargo'],
                    'area_id' => $areaIdPorCargo[$cargo['cargo']] ?? null,
                ],
            );
        }

        $cargosPorNombre = Cargo::query()->pluck('id_cargo', 'cargo');

        foreach ($usuarios as $usuario) {
            $email = strtolower((string) ($usuario['correo'] ?? ''));

            if (blank($email)) {
                continue;
            }

            $nombres = $usuario['nombres'] ?? '';
            $apellidos = $usuario['apelidos'] ?? $usuario['apellidos'] ?? '';
            $telefono = $this->nullableCsvValue($usuario['telefono'] ?? null);

            User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'nombres' => $nombres,
                    'apellidos' => $apellidos,
                    'name' => trim($nombres.' '.$apellidos),
                    'carnet' => $this->nullableCsvValue($usuario['carnet'] ?? null),
                    'telefono' => $telefono,
                    'id_area' => $areasPorNombre[$usuario['area'] ?? ''] ?? null,
                    'id_cargo' => $cargosPorNombre[$usuario['cargo'] ?? ''] ?? null,
                ],
            );
        }
    }
}
