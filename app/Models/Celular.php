<?php

namespace App\Models;

use App\Models\Concerns\PreventsForceDeleteWithDependents;
use Database\Factories\CelularFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Celular extends Model
{
    /** @use HasFactory<CelularFactory> */
    use HasFactory;

    use PreventsForceDeleteWithDependents;
    use SoftDeletes;

    protected static array $forceDeleteGuardedRelations = ['asignaciones', 'cargadores', 'asignacionesCargador'];

    protected $table = 'celulares';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'fecha_compra',
        'precio',
        'marca',
        'modelo',
        'imei',
        'procesador',
        'disco',
        'ram',
        'bateria',
        'color',
    ];

    protected function casts(): array
    {
        return [
            'fecha_compra' => 'date',
        ];
    }

    /**
     * Get the assignments for the phone.
     */
    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsigCelular::class, 'celular_id', 'id');
    }

    /**
     * Get the charger assignments for the phone.
     */
    public function asignacionesCargador(): HasMany
    {
        return $this->hasMany(AsigCargadorCel::class, 'celulares_id', 'id');
    }

    /**
     * The chargers that belong to the phone.
     */
    public function cargadores(): BelongsToMany
    {
        return $this->belongsToMany(
            CargadorCel::class,
            'asig_cargador_cel',
            'celulares_id',
            'cargador_serial',
            'id',
            'serial'
        )->withPivot(['motivo', 'fecha'])->withTimestamps();
    }
}
