<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUsuario;
use App\Models\Concerns\GuardsAgainstTrashedRelations;
use App\Models\Concerns\TogglesEstadoOnSoftDelete;
use App\Models\Concerns\WarnsOnInactiveCatalogRelations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LicenciaUsuario extends Model
{
    use BelongsToUsuario, GuardsAgainstTrashedRelations, HasFactory, SoftDeletes, TogglesEstadoOnSoftDelete, WarnsOnInactiveCatalogRelations;

    protected static array $trashGuardedRelations = ['usuario'];

    /**
     * @var array<string, array{label: string, attribute: string}>
     */
    protected static array $inactiveWarningRelations = [
        'licencia' => ['label' => 'la licencia', 'attribute' => 'nombre_licencia'],
    ];

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
     * Get the license for the assignment.
     */
    public function licencia(): BelongsTo
    {
        return $this->belongsTo(Licencia::class, 'licencia_id');
    }
}
