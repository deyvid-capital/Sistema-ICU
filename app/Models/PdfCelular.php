<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PdfCelular extends Model
{
    protected $table = 'pdf_celulares';

    protected $fillable = [
        'nombre',
        'documento_id',
        'documento_type',
        'fecha',
        'url',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    /**
     * Get the parent documentable model.
     */
    public function documento(): MorphTo
    {
        return $this->morphTo();
    }
}
