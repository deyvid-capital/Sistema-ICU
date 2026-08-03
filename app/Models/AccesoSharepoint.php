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

class AccesoSharepoint extends Model
{
    use BelongsToUsuario;
    use GuardsAgainstTrashedRelations;
    use HasFactory;
    use SoftDeletes;
    use TogglesEstadoOnSoftDelete;
    use WarnsOnInactiveCatalogRelations;

    protected static array $trashGuardedRelations = ['usuario'];

    /**
     * @var array<string, array{label: string, attribute: string}>
     */
    protected static array $inactiveWarningRelations = [
        'sitio' => ['label' => 'el sitio de SharePoint', 'attribute' => 'nombre_sitio'],
    ];

    protected $table = 'accesos_sharepoint';

    protected $fillable = [
        'usuario_id',
        'sitio_id',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
        ];
    }

    public function sitio(): BelongsTo
    {
        return $this->belongsTo(SitioSharepoint::class, 'sitio_id');
    }
}
