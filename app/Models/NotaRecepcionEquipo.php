<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class NotaRecepcionEquipo extends Model
{
    protected $table = 'nota_recepcion_equipos';

    protected $fillable = [
        'equipo_id',
        'usuarios_externo_id',
        'numero',
        'fecha',
        'nota',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    /**
     * Get the equipment for the inbound note.
     */
    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_id', 'service_tag');
    }

    /**
     * Get the external user for the inbound note.
     */
    public function usuarioExterno(): BelongsTo
    {
        return $this->belongsTo(UsuarioExterno::class, 'usuarios_externo_id');
    }

    /**
     * Get all of the note's signers.
     */
    public function firmantes(): MorphMany
    {
        return $this->morphMany(Firmante::class, 'documento');
    }
}
