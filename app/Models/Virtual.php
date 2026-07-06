<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Virtual extends Model
{

    protected $table = 'virtuales';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'maquina_virtual',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
        ];
    }

    public function accesosVirtual(): HasMany
    {
        return $this->hasMany(AccesoVirtual::class, 'virtual_id');
    }
}
