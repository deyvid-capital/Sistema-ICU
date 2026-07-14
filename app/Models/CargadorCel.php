<?php

namespace App\Models;

use App\Models\Concerns\PreventsForceDeleteWithDependents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CargadorCel extends Model
{
    use PreventsForceDeleteWithDependents;
    use SoftDeletes;

    protected static array $forceDeleteGuardedRelations = ['celulares'];

    protected $table = 'cargador_cel';

    protected $primaryKey = 'serial';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'serial',
        'modelo',
        'observacion',
    ];

    /**
     * The phones that belong to the charger.
     */
    public function celulares(): BelongsToMany
    {
        return $this->belongsToMany(
            Celular::class,
            'asig_cargador_cel',
            'cargador_serial',
            'celulares_id',
            'serial',
            'id'
        )->withPivot(['motivo', 'fecha'])->withTimestamps();
    }
}
