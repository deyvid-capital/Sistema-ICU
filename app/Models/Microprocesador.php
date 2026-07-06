<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Microprocesador extends Model
{

    protected $fillable = [
        'nombre_micro',
    ];

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class, 'microprocesador_id');
    }
}
