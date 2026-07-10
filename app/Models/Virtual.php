<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Virtual extends Model
{
    use HasFactory;

    protected $table = 'virtuales';

    protected $fillable = [
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
