<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecursoVpn extends Model
{
    use HasFactory;

    protected $table = 'recursos_vpn';

    protected $fillable = [
        'nombre_vpn',
        'descripcion',
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
        return $this->hasMany(AccesoVirtual::class, 'vpn_id');
    }
}
