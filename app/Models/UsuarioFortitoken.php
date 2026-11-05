<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUsuario;
use App\Models\Concerns\GuardsAgainstTrashedRelations;
use App\Models\Concerns\LogsIcuActivity;
use App\Models\Concerns\TogglesEstadoOnSoftDelete;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * FortiToken de un colaborador. Puede tener varios (producción, contingencia...),
 * identificados por `nombre_token`. El serial se guarda cifrado y no se audita.
 */
class UsuarioFortitoken extends Model
{
    use BelongsToUsuario;
    use GuardsAgainstTrashedRelations;
    use HasFactory;
    use LogsIcuActivity;
    use SoftDeletes;
    use TogglesEstadoOnSoftDelete;

    protected static array $trashGuardedRelations = ['usuario'];

    protected $table = 'usuario_fortitokens';

    /**
     * @var list<string>
     */
    protected $hidden = [
        'serial',
    ];

    protected $fillable = [
        'usuario_id',
        'nombre_token',
        'serial',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
            'serial' => 'encrypted',
        ];
    }

    /**
     * @return list<string>
     */
    protected function activityLogExcept(): array
    {
        return [
            'serial',
            'password',
            'remember_token',
        ];
    }
}
