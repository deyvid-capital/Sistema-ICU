<?php

namespace App\Services\Archivos;

use App\Models\MediaSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Resuelve disco, carpetas y límites de archivos combinando config/archivos.php
 * con la fila opcional en media_settings.
 */
class ArchivosService
{
    public const CACHE_KEY = 'archivos.resueltos';

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $resuelta = null;

    /**
     * @return array<string, mixed>
     */
    public function resolver(): array
    {
        return $this->resuelta ??= Cache::rememberForever(self::CACHE_KEY, fn (): array => $this->construir());
    }

    public function olvidar(): void
    {
        $this->resuelta = null;

        Cache::forget(self::CACHE_KEY);
    }

    public function disco(): string
    {
        return (string) $this->resolver()['disco'];
    }

    public function prefijo(): string
    {
        return trim((string) $this->resolver()['prefijo'], '/');
    }

    public function umbralWebpKb(): int
    {
        return (int) $this->resolver()['umbral_webp_kb'];
    }

    public function umbralWebpBytes(): int
    {
        return $this->umbralWebpKb() * 1024;
    }

    public function calidadWebp(): int
    {
        return (int) $this->resolver()['calidad_webp'];
    }

    public function thumbAncho(): int
    {
        return (int) $this->resolver()['thumb_ancho'];
    }

    public function maxImagenKb(): int
    {
        return (int) $this->resolver()['max_imagen_kb'];
    }

    public function maxPdfKb(): int
    {
        return (int) $this->resolver()['max_pdf_kb'];
    }

    public function maxLogoKb(): int
    {
        return (int) $this->resolver()['max_logo_kb'];
    }

    public function maxFaviconKb(): int
    {
        return (int) $this->resolver()['max_favicon_kb'];
    }

    public function visibilidad(): string
    {
        return (string) $this->resolver()['visibilidad'];
    }

    public function directorioPdf(string $clave): string
    {
        return (string) config("archivos.pdf.{$clave}.directorio", $clave);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function coleccionesImagen(): array
    {
        return config('archivos.colecciones_imagen', []);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function carpetasPdf(): array
    {
        return config('archivos.pdf', []);
    }

    /**
     * @return array<string, mixed>
     */
    protected function construir(): array
    {
        $fila = $this->leerFila();

        return [
            'disco' => $fila?->disco ?: config('archivos.disco'),
            'prefijo' => $fila?->prefijo ?: config('archivos.prefijo'),
            'umbral_webp_kb' => $fila?->umbral_webp_kb ?: config('archivos.umbral_webp_kb'),
            'calidad_webp' => $fila?->calidad_webp ?: config('archivos.calidad_webp'),
            'thumb_ancho' => $fila?->thumb_ancho ?: config('archivos.thumb_ancho'),
            'max_imagen_kb' => $fila?->max_imagen_kb ?: config('archivos.max_imagen_kb'),
            'max_pdf_kb' => $fila?->max_pdf_kb ?: config('archivos.max_pdf_kb'),
            'max_logo_kb' => $fila?->max_logo_kb ?: config('archivos.max_logo_kb'),
            'max_favicon_kb' => $fila?->max_favicon_kb ?: config('archivos.max_favicon_kb'),
            'visibilidad' => $fila?->visibilidad ?: config('archivos.visibilidad'),
        ];
    }

    protected function leerFila(): ?MediaSetting
    {
        try {
            if (! Schema::hasTable('media_settings')) {
                return null;
            }

            return MediaSetting::query()->first();
        } catch (Throwable) {
            return null;
        }
    }
}
