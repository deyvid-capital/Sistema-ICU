<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AsignacionEquipo extends Model
{
    use SoftDeletes;

    protected $table = 'asignacion_equipos';

    protected $fillable = [
        'service_tag',
        'usuario_id',
        'fecha_inicio',
        'fecha_fin',
        'motivo',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    /**
     * Get the equipment for the assignment.
     */
    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'service_tag', 'service_tag');
    }

    /**
     * Get the user for the assignment.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Get the delivery for the assignment.
     */
    public function entrega(): HasOne
    {
        return $this->hasOne(EntregaEquipo::class, 'asignacion_id');
    }

    /**
     * Get the reception for the assignment.
     */
    public function recepcion(): HasOne
    {
        return $this->hasOne(RecepcionEquipo::class, 'asignacion_id');
    }
}
