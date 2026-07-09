<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LicenciaUsuario extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'licencias_usuarios';

    protected $fillable = [
        'usuario_id',
        'licencia_id',
        'fecha',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'estado' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the license assignment.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Get the license for the assignment.
     */
    public function licencia(): BelongsTo
    {
        return $this->belongsTo(Licencia::class, 'licencia_id');
    }
}
