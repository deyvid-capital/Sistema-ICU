<?php

namespace App\Models;

use App\Models\Concerns\GuardsAgainstTrashedRelations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AsignacionCargador extends Model
{
    use GuardsAgainstTrashedRelations;
    use HasFactory;
    use SoftDeletes;

    protected static array $trashGuardedRelations = ['equipo', 'cargador'];

    protected $table = 'asignacion_cargadores';

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

    public function cargador(): BelongsTo
    {
        return $this->belongsTo(Cargador::class, 'serial_cargador', 'serial')->withTrashed();
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'service_tag', 'service_tag')->withTrashed();
    }
}
