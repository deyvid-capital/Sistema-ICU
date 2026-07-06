<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SitioSharepoint extends Model
{

    protected $table = 'sitios_sharepoint';

    protected $fillable = [
        'nombre_sitio',
        'url',
        'descripcion',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
        ];
    }

    public function accesos(): HasMany
    {
        return $this->hasMany(AccesoSharepoint::class, 'sitio_id');
    }
}
