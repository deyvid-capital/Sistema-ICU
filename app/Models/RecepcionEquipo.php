<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class RecepcionEquipo extends Model
{
    protected $table = 'recepcion_equipos';

    protected $fillable = [
        'asignacion_id',
        'numero',
        'fecha',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    /**
     * Get the assignment that owns the reception.
     */
    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(AsignacionEquipo::class, 'asignacion_id');
    }

    /**
     * Get all of the reception's PDF files.
     */
    public function pdfs(): MorphMany
    {
        return $this->morphMany(PdfEquipo::class, 'documento');
    }

    /**
     * Get all of the reception's signers.
     */
    public function firmantes(): MorphMany
    {
        return $this->morphMany(Firmante::class, 'documento');
    }
}
