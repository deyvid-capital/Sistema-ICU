<?php

namespace App\Console\Commands;

use App\Models\AppearanceSetting;
use App\Models\MantenimientoEquipo;
use App\Services\Archivos\ArchivosService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;

class MigrarImagenesAMediaCommand extends Command
{
    protected $signature = 'icu:migrar-imagenes-a-media
                            {--dry-run : Solo muestra qué se migraría}
                            {--disk= : Disco de origen (por defecto el de ArchivosService)}';

    protected $description = 'Migra capturas de mantenimiento y logos/favicon de columnas path a Spatie Media Library';

    public function handle(ArchivosService $archivos): int
    {
        $diskName = $this->option('disk') ?: $archivos->disco();
        $disk = Storage::disk($diskName);
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun ? 'Modo dry-run: no se escribe nada.' : 'Migrando imágenes a Media Library…');
        $this->line("Disco: {$diskName}");

        $migrados = 0;
        $omitidos = 0;
        $faltantes = 0;

        MantenimientoEquipo::query()->orderBy('id')->each(function (MantenimientoEquipo $mantenimiento) use ($disk, $diskName, $dryRun, &$migrados, &$omitidos, &$faltantes): void {
            foreach (MantenimientoEquipo::IMAGE_ATTRIBUTES as $coleccion) {
                $resultado = $this->migrarAtributo($mantenimiento, $coleccion, $disk, $diskName, $dryRun);

                if ($resultado === 'migrado') {
                    $migrados++;
                } elseif ($resultado === 'omitido') {
                    $omitidos++;
                } elseif ($resultado === 'faltante') {
                    $faltantes++;
                }
            }
        });

        AppearanceSetting::query()->orderBy('id')->each(function (AppearanceSetting $setting) use ($disk, $diskName, $dryRun, &$migrados, &$omitidos, &$faltantes): void {
            foreach (['logo_claro', 'logo_oscuro', 'favicon'] as $coleccion) {
                $resultado = $this->migrarAtributo($setting, $coleccion, $disk, $diskName, $dryRun);

                if ($resultado === 'migrado') {
                    $migrados++;
                } elseif ($resultado === 'omitido') {
                    $omitidos++;
                } elseif ($resultado === 'faltante') {
                    $faltantes++;
                }
            }
        });

        $this->newLine();
        $this->table(
            ['Migrados', 'Omitidos (ya en media)', 'Archivo ausente'],
            [[$migrados, $omitidos, $faltantes]],
        );

        return self::SUCCESS;
    }

    /**
     * @param  Model&HasMedia  $model
     */
    protected function migrarAtributo(Model $model, string $coleccion, Filesystem $disk, string $diskName, bool $dryRun): string
    {
        /** @var Model&HasMedia $model */
        if ($model->hasMedia($coleccion)) {
            return 'omitido';
        }

        $path = $model->getAttribute($coleccion);

        if (! filled($path)) {
            return 'omitido';
        }

        $class = $model::class;
        $key = $model->getKey();

        if (! $disk->exists($path)) {
            $this->warn("Falta archivo: {$path} ({$class}#{$key})");

            return 'faltante';
        }

        $this->line("→ {$class}#{$key} [{$coleccion}] {$path}");

        if ($dryRun) {
            return 'migrado';
        }

        try {
            $model
                ->addMediaFromDisk($path, $diskName)
                ->preservingOriginal()
                ->usingFileName(basename((string) $path))
                ->toMediaCollection($coleccion, $diskName);
        } catch (\Throwable $exception) {
            $this->error("No se pudo migrar {$path} ({$class}#{$key}): {$exception->getMessage()}");

            return 'faltante';
        }

        return 'migrado';
    }
}
