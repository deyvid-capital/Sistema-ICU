<?php

namespace Database\Seeders;

use App\Enums\EstadoCargador;
use App\Enums\EstadoEquipo;
use App\Models\Cargador;
use App\Models\Equipo;
use App\Models\MemoriaRam;
use App\Models\Microprocesador;
use App\Models\ModeloLaptop;
use App\Models\TarjetaVideo;
use Database\Seeders\Concerns\ReadsCsvData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class EquiposSeeder extends Seeder
{
    use ReadsCsvData;

    public function run(): void
    {
        $modelosPorCsvId = $this->seedModelos();
        $microsPorCsvId = $this->seedMicroprocesadores();
        $ramsPorCsvId = $this->seedMemoriasRam();
        $gpusPorCsvId = $this->seedTarjetasVideo();

        $this->seedEquipos($modelosPorCsvId, $microsPorCsvId, $ramsPorCsvId, $gpusPorCsvId);
        $this->seedCargadores();
    }

    /**
     * @return array<int, int>
     */
    private function seedModelos(): array
    {
        $idsPorCsv = [];

        foreach ($this->rowsFromCsv('ModeloLaptops.csv') as $row) {
            $csvId = (int) $row['Modelo_ID'];
            $modelo = ModeloLaptop::query()->updateOrCreate(
                ['nombre' => $row['Modelo']],
                ['nombre' => $row['Modelo']],
            );
            $idsPorCsv[$csvId] = $modelo->id;
        }

        return $idsPorCsv;
    }

    /**
     * @return array<int, int>
     */
    private function seedMicroprocesadores(): array
    {
        $idsPorCsv = [];

        foreach ($this->rowsFromCsv('Microprocesadores.csv') as $row) {
            $csvId = (int) $row['Procesador_ID'];
            $micro = Microprocesador::query()->updateOrCreate(
                ['nombre_micro' => $row['Procesador']],
                ['nombre_micro' => $row['Procesador']],
            );
            $idsPorCsv[$csvId] = $micro->id;
        }

        return $idsPorCsv;
    }

    /**
     * @return array<int, int>
     */
    private function seedMemoriasRam(): array
    {
        $idsPorCsv = [];

        foreach ($this->rowsFromCsv('MemoriasRam.csv') as $row) {
            $csvId = (int) $row['Ram_ID'];
            $ram = MemoriaRam::query()->updateOrCreate(
                ['tipo_ram' => $row['Memoria_RAM']],
                ['tipo_ram' => $row['Memoria_RAM']],
            );
            $idsPorCsv[$csvId] = $ram->id;
        }

        return $idsPorCsv;
    }

    /**
     * @return array<int, int>
     */
    private function seedTarjetasVideo(): array
    {
        $idsPorCsv = [];

        foreach ($this->rowsFromCsv('TarjetaVideo.csv') as $row) {
            $csvId = (int) $row['GPU_ID'];
            $gpu = TarjetaVideo::query()->updateOrCreate(
                ['nombre_gpu' => $row['Tarjeta_Video']],
                ['nombre_gpu' => $row['Tarjeta_Video']],
            );
            $idsPorCsv[$csvId] = $gpu->id;
        }

        return $idsPorCsv;
    }

    /**
     * @param  array<int, int>  $modelosPorCsvId
     * @param  array<int, int>  $microsPorCsvId
     * @param  array<int, int>  $ramsPorCsvId
     * @param  array<int, int>  $gpusPorCsvId
     */
    private function seedEquipos(
        array $modelosPorCsvId,
        array $microsPorCsvId,
        array $ramsPorCsvId,
        array $gpusPorCsvId,
    ): void {
        foreach ($this->rowsFromCsv('Equipos.csv') as $row) {
            $serviceTag = $this->nullableCsvValue($row['Service_Tag'] ?? null);

            if ($serviceTag === null) {
                continue;
            }

            Equipo::query()->updateOrCreate(
                ['service_tag' => $serviceTag],
                [
                    'service_tag' => $serviceTag,
                    'modelo_id' => $this->catalogId($modelosPorCsvId, $row['Modelo_ID'] ?? null, 'Modelo_ID', $serviceTag),
                    'microprocesador_id' => $this->catalogId($microsPorCsvId, $row['Procesador_ID'] ?? null, 'Procesador_ID', $serviceTag),
                    'ram_id' => $this->catalogId($ramsPorCsvId, $row['Ram_ID'] ?? null, 'Ram_ID', $serviceTag),
                    'gpu_id' => $this->catalogId($gpusPorCsvId, $row['GPU_ID'] ?? null, 'GPU_ID', $serviceTag),
                    'disco_duro' => $this->nullableCsvValue($row['Disco'] ?? null),
                    'proveedor' => $this->nullableCsvValue($row['Proveedor'] ?? null),
                    'inicio_garantia' => $this->parseCsvDate($row['Fecha_Compra'] ?? null),
                    'venc_garantia' => $this->parseCsvDate($row['Fecha_Garantia'] ?? null),
                    'nombre_equipo' => $this->nombreEquipo($row['Nombre_Equipo'] ?? null),
                    'licencia_so' => $this->nullableCsvValue($row['Licencia'] ?? null),
                    'estado' => EstadoEquipo::SinAsignar,
                ],
            );
        }
    }

    private function seedCargadores(): void
    {
        foreach ($this->rowsFromCsv('Cargadores.csv') as $row) {
            $serial = $this->nullableCsvValue($row['SERIAL'] ?? null);

            if ($serial === null) {
                continue;
            }

            Cargador::query()->updateOrCreate(
                ['serial' => $serial],
                [
                    'serial' => $serial,
                    'marca' => $this->nullableCsvValue($row['MARCA'] ?? null),
                    'modelo' => $this->nullableCsvValue($row['MODELO'] ?? null),
                    'estado' => $this->estadoCargador($row['ESTADO'] ?? null),
                    'observaciones' => null,
                ],
            );
        }
    }

    /**
     * @param  array<int, int>  $idsPorCsv
     */
    private function catalogId(array $idsPorCsv, ?string $csvId, string $columna, string $serviceTag): int
    {
        $id = $idsPorCsv[(int) $csvId] ?? null;

        if ($id === null) {
            throw new \RuntimeException("{$columna} «{$csvId}» no existe en el catálogo (equipo {$serviceTag}).");
        }

        return $id;
    }

    private function nombreEquipo(?string $value): string
    {
        $nombre = $this->nullableCsvValue($value);

        if ($nombre === null || $nombre === '0') {
            return 'BOCS';
        }

        return $nombre;
    }

    private function parseCsvDate(?string $value): ?string
    {
        $value = $this->nullableCsvValue($value);

        if ($value === null) {
            return null;
        }

        $fecha = Carbon::createFromFormat('j/n/Y', $value);

        if ($fecha === false) {
            throw new \RuntimeException("Fecha inválida: {$value}");
        }

        return $fecha->toDateString();
    }

    private function estadoCargador(?string $value): string
    {
        $value = $this->nullableCsvValue($value);

        if ($value === null) {
            return EstadoCargador::Disponible->value;
        }

        $estado = EstadoCargador::tryFrom(mb_strtolower($value))
            ?? collect(EstadoCargador::cases())->first(
                fn (EstadoCargador $estado): bool => strcasecmp($estado->getLabel(), $value) === 0,
            );

        return $estado?->value ?? EstadoCargador::Disponible->value;
    }
}
