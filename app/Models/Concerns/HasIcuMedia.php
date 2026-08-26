<?php

namespace App\Models\Concerns;

use App\Services\Archivos\ArchivosService;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait HasIcuMedia
{
    use InteractsWithMedia;

    /**
     * Conversiones compartidas: thumb siempre; webp si supera el umbral (raster).
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        if ($media !== null && ! $this->debeConvertir($media)) {
            return;
        }

        $archivos = app(ArchivosService::class);

        $this->addMediaConversion('thumb')
            ->width($archivos->thumbAncho())
            ->performOnCollections(...$this->coleccionesConConversion());

        if ($media !== null && $media->size > $archivos->umbralWebpBytes()) {
            $this->addMediaConversion('webp')
                ->format('webp')
                ->quality($archivos->calidadWebp())
                ->performOnCollections(...$this->coleccionesConConversion());
        }
    }

    /**
     * @return list<string>
     */
    abstract protected function coleccionesConConversion(): array;

    protected function debeConvertir(Media $media): bool
    {
        $mime = (string) $media->mime_type;

        if (! str_starts_with($mime, 'image/')) {
            return false;
        }

        return ! in_array($mime, [
            'image/svg+xml',
            'image/x-icon',
            'image/vnd.microsoft.icon',
        ], true);
    }

    public function hasStoredImage(string $collection): bool
    {
        if ($this->hasMedia($collection)) {
            return true;
        }

        $path = $this->getAttribute($collection);

        return filled($path)
            && Storage::disk(app(ArchivosService::class)->disco())->exists($path);
    }

    public function storedImageUrl(string $collection, string $conversion = ''): ?string
    {
        if ($this->hasMedia($collection)) {
            if ($conversion === 'webp' && ! $this->getFirstMedia($collection)?->hasGeneratedConversion('webp')) {
                $conversion = '';
            }

            if ($conversion === 'thumb' && ! $this->getFirstMedia($collection)?->hasGeneratedConversion('thumb')) {
                $conversion = '';
            }

            $url = $this->getFirstMediaUrl($collection, $conversion);

            return filled($url) ? $url : null;
        }

        $path = $this->getAttribute($collection);

        if (! filled($path)) {
            return null;
        }

        $disk = Storage::disk(app(ArchivosService::class)->disco());

        return $disk->exists($path) ? $disk->url($path) : null;
    }

    /**
     * Borra del disco el archivo apuntado por la columna heredada (la ruta de la
     * implementación previa a Media Library) y limpia el atributo, para no dejar
     * residuos cuando la colección ya tiene su propio archivo.
     *
     * Solo debe llamarse cuando la colección tiene media: mientras no la tenga,
     * ese archivo es el que el panel está usando.
     */
    public function purgarArchivoHeredado(string $collection): bool
    {
        $path = $this->getAttribute($collection);

        if (! filled($path)) {
            return false;
        }

        $disk = Storage::disk(app(ArchivosService::class)->disco());

        if ($disk->exists($path)) {
            $disk->delete($path);
        }

        $this->setAttribute($collection, null);

        return true;
    }

    public function storedImageFilename(string $collection): ?string
    {
        $media = $this->getFirstMedia($collection);

        if ($media !== null) {
            return $media->file_name;
        }

        $path = $this->getAttribute($collection);

        return filled($path) ? basename((string) $path) : null;
    }

    /**
     * Disco configurado para nuevas colecciones de este modelo.
     */
    protected function discoMedia(): string
    {
        return app(ArchivosService::class)->disco();
    }
}
