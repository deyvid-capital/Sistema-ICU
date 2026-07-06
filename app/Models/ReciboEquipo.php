<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReciboEquipo extends Model
{
    use SoftDeletes;


    protected $table = 'recibo_equipo';

    protected $fillable = [
        'equipo_id',
        'fecha',
        'url',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_id', 'service_tag');
    }
}
