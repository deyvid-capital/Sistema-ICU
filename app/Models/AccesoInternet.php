<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUsuario;
use App\Models\Concerns\CascadesDependentSoftDeletes;
use App\Models\Concerns\GuardsAgainstTrashedRelations;
use App\Models\Concerns\TogglesEstadoOnSoftDelete;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccesoInternet extends Model
{
    use BelongsToUsuario;
    use CascadesDependentSoftDeletes;
    use GuardsAgainstTrashedRelations;
    use HasFactory;
    use SoftDeletes;
    use TogglesEstadoOnSoftDelete;

    protected static array $trashGuardedRelations = ['usuario'];

    protected $table = 'accesos_wifi';

    protected $fillable = [
        'usuario_id',
        'usuario_red',
        'nivel_acceso',
        'fortitoken',
        'estado',
        'fecha',
        'observaciones',
    ];

    /**
     * @return array<string>
     */
    protected function dependentSoftDeleteRelations(): array
    {
        return [
            'accesosVirtual',
        ];
    }

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
            'fecha' => 'date',
        ];
    }

    public function accesosVirtual(): HasMany
    {
        return $this->hasMany(AccesoVirtual::class, 'internet_id');
    }
}
