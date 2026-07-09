<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Licencia extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre_licencia',
        'categoria',
        'descripcion',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
        ];
    }

    /**
     * Get the user assignments for the license.
     */
    public function licenciasUsuarios(): HasMany
    {
        return $this->hasMany(LicenciaUsuario::class, 'licencia_id');
    }

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
