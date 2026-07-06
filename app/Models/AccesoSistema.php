<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccesoSistema extends Model
{
    protected $table = 'accesos_sistemas';

    protected $fillable = [
        'usuario_id',
        'id_sistema',
        'usuario_sistema',
        'nivel_acceso',
        'estado',
        'fecha_inicio',
        'fecha_fin',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    /**
     * Get the user that owns the access.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Get the system for the access.
     */
    public function sistema(): BelongsTo
    {
        return $this->belongsTo(Sistema::class, 'id_sistema', 'id_sistema');
    }
}
