<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUsuario;
use App\Models\Concerns\GuardsAgainstTrashedRelations;
use App\Models\Concerns\TogglesEstadoOnSoftDelete;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AzureAd extends Model
{
    use BelongsToUsuario, GuardsAgainstTrashedRelations, HasFactory, SoftDeletes, TogglesEstadoOnSoftDelete;

    protected static array $trashGuardedRelations = ['usuario'];

    protected $table = 'cuentas_azure_ad';

    protected $fillable = [
        'usuario_id',
        'iniciales',
        'sincronizacion',
        'mfa',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'mfa' => 'boolean',
            'estado' => 'boolean',
        ];
    }
}
