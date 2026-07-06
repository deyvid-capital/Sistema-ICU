<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccUdCompartida extends Model
{
    use SoftDeletes;


    protected $table = 'acc_ud_compartidas';

    protected $fillable = [
        'usuario_id',
        'unidad_compartida',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
