<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Firmante extends Model
{
    protected $fillable = [
        'usuario_id',
        'fecha',
        'documento_id',
        'documento_type',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    /**
     * Get the user that signed the document.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Get the parent documentable model.
     */
    public function documento(): MorphTo
    {
        return $this->morphTo();
    }
}
