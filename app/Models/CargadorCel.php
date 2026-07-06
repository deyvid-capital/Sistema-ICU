<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CargadorCel extends Model
{
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
