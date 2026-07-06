<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemoriaRam extends Model
{

    protected $table = 'memorias_ram';

    protected $fillable = [
        'tipo_ram',
    ];

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class, 'ram_id');
    }
}
