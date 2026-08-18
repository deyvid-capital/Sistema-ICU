<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUsuario;
use App\Models\Concerns\GuardsAgainstTrashedRelations;
use Database\Factories\AsigCelularFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AsigCelular extends Model
{
    /** @use HasFactory<AsigCelularFactory> */
    use BelongsToUsuario;

    use GuardsAgainstTrashedRelations;
    use HasFactory;
    use SoftDeletes;

    protected static array $trashGuardedRelations = ['usuario', 'celular'];

    protected $table = 'asignaciones_celulares';

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
        return $this->belongsTo(Celular::class, 'celular_id', 'id')->withTrashed();
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
