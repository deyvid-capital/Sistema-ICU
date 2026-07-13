<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUsuario;
use App\Models\Concerns\GuardsAgainstTrashedRelations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class AsignacionEquipo extends Model
{
    use BelongsToUsuario;
    use GuardsAgainstTrashedRelations;
    use SoftDeletes;

    protected static array $trashGuardedRelations = ['usuario'];

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
