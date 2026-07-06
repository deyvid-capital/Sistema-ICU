<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MantenimientoEquipo extends Model
{
    protected $table = 'mantenimiento_equipo';

    protected $fillable = [
        'service_tag',
        'fecha_soporte',
        'cap_antes',
        'cap_despues',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_soporte' => 'date',
        ];
    }

    /**
     * Get the equipment for the maintenance record.
     */
    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'service_tag', 'service_tag');
    }
}
