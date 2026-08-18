<?php

namespace App\Models;

use App\Models\Concerns\PreventsForceDeleteWithDependents;
use Database\Factories\CargadorCelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CargadorCel extends Model
{
    /** @use HasFactory<CargadorCelFactory> */
    use HasFactory;

    use PreventsForceDeleteWithDependents;
    use SoftDeletes;

    protected static array $forceDeleteGuardedRelations = ['celulares', 'asignaciones'];

    protected $table = 'cargadores_celulares';

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
            'asignaciones_cargadores_celulares',
            'cargador_serial',
            'celulares_id',
            'serial',
            'id'
        )->withPivot(['motivo', 'fecha'])->withTimestamps();
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsigCargadorCel::class, 'cargador_serial', 'serial');
    }
}
