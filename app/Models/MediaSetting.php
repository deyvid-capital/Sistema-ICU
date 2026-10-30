<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaSetting extends Model
{
    protected $fillable = [
        'disco',
        'prefijo',
        'umbral_webp_kb',
        'calidad_webp',
        'thumb_ancho',
        'max_imagen_kb',
        'max_pdf_kb',
        'max_logo_kb',
        'max_favicon_kb',
        'visibilidad',
    ];

    /**
     * Configuración única de archivos: siempre se trabaja sobre la primera fila.
     */
    public static function actual(): self
    {
        return static::query()->first() ?? new static([
            'disco' => config('archivos.disco'),
            'prefijo' => config('archivos.prefijo'),
            'umbral_webp_kb' => config('archivos.umbral_webp_kb'),
            'calidad_webp' => config('archivos.calidad_webp'),
            'thumb_ancho' => config('archivos.thumb_ancho'),
            'max_imagen_kb' => config('archivos.max_imagen_kb'),
            'max_pdf_kb' => config('archivos.max_pdf_kb'),
            'max_logo_kb' => config('archivos.max_logo_kb'),
            'max_favicon_kb' => config('archivos.max_favicon_kb'),
            'visibilidad' => 'private',
        ]);
    }
}
