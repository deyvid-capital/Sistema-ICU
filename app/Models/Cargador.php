<?php

namespace App\Models;

use App\Enums\EstadoCargador;
use App\Models\Concerns\PreventsForceDeleteWithDependents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cargador extends Model
{
    use HasFactory;
    use PreventsForceDeleteWithDependents;
    use SoftDeletes;

    protected static array $forceDeleteGuardedRelations = ['asignaciones'];

    protected $table = 'cargadores';

    protected $primaryKey = 'serial';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'serial',
        'marca',
        'modelo',
        'estado',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoCargador::class,
        ];
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionCargador::class, 'serial_cargador', 'serial');
    }

    public function asignacion(): HasOne
    {
        return $this->hasOne(AsignacionCargador::class, 'serial_cargador', 'serial')
            ->whereNull('fecha_fin')
            ->latest('fecha_inicio');
    }

    public function asignacionActiva(): HasOne
    {
        return $this->asignacion();
    }
}
