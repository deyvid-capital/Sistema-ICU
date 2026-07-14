<?php

namespace App\Models;

use App\Models\Concerns\PreventsForceDeleteWithDependents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cargador extends Model
{
    use HasFactory;
    use PreventsForceDeleteWithDependents;
    use SoftDeletes;

    protected static array $forceDeleteGuardedRelations = ['asignacion'];

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

    public function asignacion(): HasOne
    {
        return $this->hasOne(AsignacionCargador::class, 'serial_cargador', 'serial');
    }
}
