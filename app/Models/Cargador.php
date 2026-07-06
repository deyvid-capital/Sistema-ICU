<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cargador extends Model
{
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
