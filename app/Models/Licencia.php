<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Licencia extends Model
{
    protected $fillable = [
        'nombre_licencia',
        'categoria',
        'descripcion',
    ];

    /**
     * The users that belong to the license.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'licencias_usuarios',
            'licencia_id',
            'usuario_id',
            'id',
            'id'
        )->withPivot(['fecha', 'estado'])->withTimestamps();
    }
}
