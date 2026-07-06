<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class EntregaEquipo extends Model
{
    protected $table = 'entrega_equipos';

    protected $fillable = [
        'asignacion_id',
        'numero',
        'fecha',
        'mouse',
        'cooler',
        'audifonos',
        'observaciones',
        'nota',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'mouse' => 'boolean',
            'cooler' => 'boolean',
            'audifonos' => 'boolean',
        ];
    }

    /**
     * Get the assignment that owns the delivery.
     */
    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(AsignacionEquipo::class, 'asignacion_id');
    }

    /**
     * Get all of the delivery's PDF files.
     */
    public function pdfs(): MorphMany
    {
        return $this->morphMany(PdfEquipo::class, 'documento');
    }

    /**
     * Get all of the delivery's signers.
     */
    public function firmantes(): MorphMany
    {
        return $this->morphMany(Firmante::class, 'documento');
    }
}
