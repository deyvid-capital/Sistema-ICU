<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsignacionCargador extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'serial_cargador',
        'service_tag',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    /**
     * Get the charger for the assignment.
     */
    public function cargador(): BelongsTo
    {
        return $this->belongsTo(Cargador::class, 'serial_cargador', 'serial');
    }

    /**
     * Get the equipment for the assignment.
     */
    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'service_tag', 'service_tag');
    }
}
