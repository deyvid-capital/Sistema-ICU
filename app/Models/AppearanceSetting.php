<?php

namespace App\Models;

use App\Models\Concerns\HasIcuMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

class AppearanceSetting extends Model implements HasMedia
{
    use HasIcuMedia;

    protected $fillable = [
        'tema',
        'color_primario',
        'color_gris',
        'color_sidebar_claro',
        'color_sidebar_oscuro',
        'color_topbar_claro',
        'color_topbar_oscuro',
        'logo_claro',
        'logo_oscuro',
        'favicon',
        'alto_logo',
        'fondo_logo',
    ];

    /**
     * La configuración es única para todo el panel: siempre se trabaja sobre la
     * primera fila, creándola en memoria si todavía no existe.
     */
    public static function actual(): self
    {
        return static::query()->first() ?? new static([
            'tema' => config('apariencia.tema_por_defecto'),
        ]);
    }

    public function registerMediaCollections(): void
    {
        $disk = $this->discoMedia();

        $this->addMediaCollection('logo_claro')
            ->useDisk($disk)
            ->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml']);

        $this->addMediaCollection('logo_oscuro')
            ->useDisk($disk)
            ->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml']);

        $this->addMediaCollection('favicon')
            ->useDisk($disk)
            ->singleFile()
            ->acceptsMimeTypes(['image/x-icon', 'image/vnd.microsoft.icon', 'image/png', 'image/svg+xml']);
    }

    /**
     * @return list<string>
     */
    protected function coleccionesConConversion(): array
    {
        return ['logo_claro', 'logo_oscuro'];
    }

    public function clearBrandMedia(): void
    {
        $this->clearMediaCollection('logo_claro');
        $this->clearMediaCollection('logo_oscuro');
        $this->clearMediaCollection('favicon');
    }
}
