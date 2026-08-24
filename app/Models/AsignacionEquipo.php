<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUsuario;
use App\Models\Concerns\GuardsAgainstTrashedRelations;
use App\Models\Concerns\LogsIcuActivity;
use App\Models\Concerns\PreventsForceDeleteWithDependents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class AsignacionEquipo extends Model
{
    use BelongsToUsuario;
    use GuardsAgainstTrashedRelations;
    use HasFactory;
    use LogsIcuActivity;
    use PreventsForceDeleteWithDependents;
    use SoftDeletes;

    protected static array $trashGuardedRelations = ['usuario', 'equipo'];

    protected static array $forceDeleteGuardedRelations = ['entrega', 'recepcion'];

    protected $table = 'asignaciones_equipos';

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

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'service_tag', 'service_tag')->withTrashed();
    }

    public function entrega(): HasOne
    {
        return $this->hasOne(EntregaEquipo::class, 'asignacion_id');
    }

    public function recepcion(): HasOne
    {
        return $this->hasOne(RecepcionEquipo::class, 'asignacion_id');
    }
}
