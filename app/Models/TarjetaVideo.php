<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TarjetaVideo extends Model
{

    protected $table = 'tarjetas_video';

    protected $fillable = [
        'nombre_gpu',
    ];

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class, 'gpu_id');
    }
}
