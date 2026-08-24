<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUsuario;
use App\Models\Concerns\GuardsAgainstTrashedRelations;
use App\Models\Concerns\LogsIcuActivity;
use App\Models\Concerns\TogglesEstadoOnSoftDelete;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccesoSistema extends Model
{
    use BelongsToUsuario;
    use GuardsAgainstTrashedRelations;
    use HasFactory;
    use LogsIcuActivity;
    use SoftDeletes;
    use TogglesEstadoOnSoftDelete;

    protected static array $trashGuardedRelations = ['usuario'];

    protected $table = 'accesos_sistemas';

    protected $fillable = [
        'usuario_id',
        'id_sistema',
        'usuario_sistema',
        'nivel_acceso',
        'estado',
        'fecha_inicio',
        'fecha_fin',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    /**
     * Get the system for the access.
     */
    public function sistema(): BelongsTo
    {
        return $this->belongsTo(Sistema::class, 'id_sistema', 'id_sistema');
    }
}
