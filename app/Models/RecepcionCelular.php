<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class RecepcionCelular extends Model
{
    protected $table = 'recepcion_celulares';

    protected $fillable = [
        'asignacion_cel_id',
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
     * Get the phone assignment that owns the reception.
     */
    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(AsigCelular::class, 'asignacion_cel_id');
    }

    /**
     * Get all of the reception's PDF files.
     */
    public function pdfs(): MorphMany
    {
        return $this->morphMany(PdfCelular::class, 'documento');
    }

    /**
     * Get all of the reception's signers.
     */
    public function firmantes(): MorphMany
    {
        return $this->morphMany(Firmante::class, 'documento');
    }
}
