<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Cargo;
use App\Models\User;
use Illuminate\Database\Seeder;

class CatalogoPersonalSeeder extends Seeder
{
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
            $telefono = $usuario['telefono'] ?? null;

            if (in_array($telefono, [null, '', '-'], true)) {
                $telefono = null;
            }

            User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'nombres' => $nombres,
                    'apellidos' => $apellidos,
                    'name' => trim($nombres.' '.$apellidos),
                    'carnet' => filled($usuario['carnet'] ?? null) ? $usuario['carnet'] : null,
                    'telefono' => $telefono,
                    'id_area' => $areasPorNombre[$usuario['area'] ?? ''] ?? null,
                    'id_cargo' => $cargosPorNombre[$usuario['cargo'] ?? ''] ?? null,
                ],
            );
        }
    }

    /**
     * @return list<array<string, string>>
     */
    private function rowsFromCsv(string $filename): array
    {
        $path = database_path('data/'.$filename);
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new \RuntimeException("No se pudo leer {$path}.");
        }

        try {
            $header = fgetcsv($handle, separator: ';');

            if ($header === false) {
                return [];
            }

            $header = array_map($this->cleanCsvValue(...), $header);
            $rows = [];

            while (($row = fgetcsv($handle, separator: ';')) !== false) {
                if ($row === [null]) {
                    continue;
                }

                $values = array_pad(array_map($this->cleanCsvValue(...), $row), count($header), '');
                $rows[] = array_combine($header, $values);
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    private function cleanCsvValue(?string $value): string
    {
        $value = (string) $value;
        $value = preg_replace('/^\xEF\xBB\xBF/u', '', $value) ?? $value;

        if (! mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
        }

        $value = str_replace("\u{00A0}", ' ', $value);

        return trim($value);
    }
}
