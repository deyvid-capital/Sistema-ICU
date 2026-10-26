<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FirmaSetting extends Model
{
    protected $fillable = [
        'requiere_2fa_al_firmar',
        'intentos_maximos',
        'minutos_bloqueo',
        'plazo_dias_por_defecto',
        'recordatorio_horas_por_defecto',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requiere_2fa_al_firmar' => 'boolean',
            'intentos_maximos' => 'integer',
            'minutos_bloqueo' => 'integer',
            'plazo_dias_por_defecto' => 'integer',
            'recordatorio_horas_por_defecto' => 'integer',
        ];
    }

    /**
     * Configuración única: siempre se trabaja sobre la primera fila.
     */
    public static function actual(): self
    {
        return static::query()->first() ?? new static([
            'requiere_2fa_al_firmar' => false,
            'intentos_maximos' => 5,
            'minutos_bloqueo' => 15,
            'plazo_dias_por_defecto' => 3,
            'recordatorio_horas_por_defecto' => 24,
        ]);
    }
}
