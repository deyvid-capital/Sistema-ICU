<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModeloLaptop extends Model
{

    protected $table = 'modelos_laptops';

    protected $fillable = [
        'nombre',
    ];

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class, 'modelo_id');
    }
}
