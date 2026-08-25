<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppearanceSetting extends Model
{
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
}
