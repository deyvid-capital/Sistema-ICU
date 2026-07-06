<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AsigCelular extends Model
{
    use SoftDeletes;

    protected $table = 'asig_celular';

    protected $fillable = [
        'celular_id',
        'usuario_id',
        'fecha_ini',
        'fecha_fin',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ini' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    /**
     * Get the phone for the assignment.
     */
    public function celular(): BelongsTo
    {
        return $this->belongsTo(Celular::class, 'celular_id', 'id');
    }

    /**
     * Get the user for the assignment.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Get the deliveries for the assignment.
     */
    public function entregas(): HasMany
    {
        return $this->hasMany(EntregaCelular::class, 'asignacion_cel_id');
    }

    /**
     * Get the receptions for the assignment.
     */
    public function recepciones(): HasMany
    {
        return $this->hasMany(RecepcionCelular::class, 'asignacion_cel_id');
    }
}
