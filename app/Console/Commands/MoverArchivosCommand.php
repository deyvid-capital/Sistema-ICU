<?php

namespace App\Console\Commands;

use App\Models\AppearanceSetting;
use App\Models\MantenimientoEquipo;
use App\Models\PdfCelular;
use App\Models\PdfEquipo;
use App\Models\PdfMovimiento;
use App\Models\ReciboEquipo;
use App\Services\Archivos\ArchivosService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

/**
 * Lleva cada archivo a su disco: lo confidencial (PDF, capturas de mantenimiento) al
 * disco confidencial configurado (`local` o `s3`) y la marca (logos, favicon) al
 * disco `public`. Copia, comprueba y recién entonces borra el original. Nunca deja
 * un archivo confidencial en el disco `public`.
 */
class MoverArchivosCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'archivos:mover
                            {--simular : Solo muestra lo que movería, sin tocar nada}';

    /**
     * @var string
     */
    protected $description = 'Mueve los archivos existentes a su disco (confidenciales a local/s3, marca a public)';

    /**
     * @var array<class-string<Model>, list<string>>
     */
    private const IMAGENES_HEREDADAS = [
        MantenimientoEquipo::class => ['cap_antes', 'cap_despues'],
        AppearanceSetting::class => ['logo_claro', 'logo_oscuro', 'favicon'],
    ];

    /**
     * @var list<class-string<Model>>
     */
    private const MODELOS_PDF = [PdfEquipo::class, PdfCelular::class, PdfMovimiento::class, ReciboEquipo::class];

    private int $movidos = 0;

    private int $errores = 0;

    public function handle(ArchivosService $archivos): int
    {
        $confidencial = $archivos->disco();
        $publico = $archivos->discoPublico();

        $this->info("Confidenciales → {$confidencial} · Marca → {$publico}".($this->option('simular') ? ' (simulación)' : ''));

        foreach (self::MODELOS_PDF as $modelo) {
            $this->moverPdfs($modelo, $confidencial);
        }

        foreach (self::IMAGENES_HEREDADAS as $modelo => $columnas) {
            $destino = $modelo === AppearanceSetting::class ? $publico : $confidencial;
            $this->moverColumnas($modelo, $columnas, $destino);
        }

        Media::query()->each(function (Media $media) use ($confidencial, $publico): void {
            $this->moverMedia($media, $media->model_type === AppearanceSetting::class ? $publico : $confidencial);
        });

        $this->info("Archivos movidos: {$this->movidos}. Errores: {$this->errores}.");

        return $this->errores === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  class-string<Model>  $modelo
     */
    private function moverPdfs(string $modelo, string $destino): void
    {
        $consulta = $modelo::query()->whereNotNull('url');

        if (method_exists($modelo, 'bootSoftDeletes')) {
            $consulta->withTrashed();
        }

        $consulta->each(fn (Model $registro) => $this->moverRuta((string) $registro->getAttribute('url'), $destino));
    }

    /**
     * @param  class-string<Model>  $modelo
     * @param  list<string>  $columnas
     */
    private function moverColumnas(string $modelo, array $columnas, string $destino): void
    {
        $consulta = $modelo::query();

        if (method_exists($modelo, 'bootSoftDeletes')) {
            $consulta->withTrashed();
        }

        $consulta->each(function (Model $registro) use ($columnas, $destino): void {
            foreach ($columnas as $columna) {
                if (filled($registro->getAttribute($columna))) {
                    $this->moverRuta((string) $registro->getAttribute($columna), $destino);
                }
            }
        });
    }

    /**
     * Mueve el archivo original y sus conversiones (toda la carpeta del media).
     */
    private function moverMedia(Media $media, string $destino): void
    {
        if ($media->disk === $destino && ($media->conversions_disk ?? $destino) === $destino) {
            return;
        }

        $carpeta = dirname($media->getPathRelativeToRoot());
        $origen = Storage::disk($media->disk);
        $archivos = $origen->allFiles($carpeta);

        $this->line("  media #{$media->getKey()} ({$media->collection_name}): {$media->disk} → {$destino}, ".count($archivos).' archivo(s)');

        if ($this->option('simular')) {
            return;
        }

        foreach ($archivos as $ruta) {
            if (! $this->copiar($origen, Storage::disk($destino), $ruta, $destino)) {
                return;
            }
        }

        $discoAnterior = $media->disk;
        $media->forceFill(['disk' => $destino, 'conversions_disk' => $destino])->saveQuietly();
        Storage::disk($discoAnterior)->deleteDirectory($carpeta);
        $this->movidos += count($archivos);
    }

    /**
     * Busca la ruta en los otros discos y la trae al de destino.
     */
    private function moverRuta(string $ruta, string $destino): void
    {
        $discoDestino = Storage::disk($destino);

        foreach ($this->discosOrigen($destino) as $nombre) {
            $origen = Storage::disk($nombre);

            if (! $this->existe($origen, $ruta)) {
                continue;
            }

            if ($discoDestino->exists($ruta)) {
                $this->quitarDuplicado($origen, $discoDestino, $ruta, $nombre);

                continue;
            }

            $this->line("  {$nombre}:{$ruta} → {$destino}");

            if (! $this->option('simular') && $this->copiar($origen, $discoDestino, $ruta, $destino)) {
                $origen->delete($ruta);
                $this->movidos++;
            }
        }
    }

    /**
     * La ruta ya está en el destino: si la copia de origen es idéntica, se borra; si
     * difiere, se deja y se avisa para revisarla a mano.
     */
    private function quitarDuplicado(Filesystem $origen, Filesystem $destino, string $ruta, string $nombre): void
    {
        if (hash('sha256', (string) $origen->get($ruta)) !== hash('sha256', (string) $destino->get($ruta))) {
            $this->warn("  {$nombre}:{$ruta} difiere del archivo en el destino; se deja para revisar a mano.");

            return;
        }

        $this->line("  {$nombre}:{$ruta} duplicado idéntico, se borra.");

        if (! $this->option('simular')) {
            $origen->delete($ruta);
        }
    }

    private function copiar(Filesystem $origen, Filesystem $destino, string $ruta, string $nombreDestino): bool
    {
        try {
            $contenido = (string) $origen->get($ruta);
            $visibilidad = $nombreDestino === ArchivosService::DISCO_PUBLICO ? 'public' : 'private';

            if (! $destino->put($ruta, $contenido, ['visibility' => $visibilidad])
                || hash('sha256', (string) $destino->get($ruta)) !== hash('sha256', $contenido)) {
                throw new \RuntimeException('la copia no coincide con el original');
            }

            return true;
        } catch (Throwable $exception) {
            $this->error("  No se pudo mover {$ruta}: {$exception->getMessage()}");
            $this->errores++;

            return false;
        }
    }

    private function existe(Filesystem $disco, string $ruta): bool
    {
        try {
            return $disco->exists($ruta);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return list<string>
     */
    private function discosOrigen(string $destino): array
    {
        $discos = ['public', 'local'];

        if (filled(config('filesystems.disks.s3.bucket'))) {
            $discos[] = 's3';
        }

        return array_values(array_diff($discos, [$destino]));
    }
}
