<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SoftwareExc extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'software_exc';

    protected $fillable = [
        'usuario_id',
        'software_id',
        'tipo_excepcion',
        'motivo',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class, 'software_id', 'id_software');
    }
}
