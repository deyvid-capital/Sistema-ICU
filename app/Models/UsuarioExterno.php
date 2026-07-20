<?php

namespace App\Models;

use App\Models\Concerns\PreventsForceDeleteWithDependents;
use Database\Factories\UsuarioExternoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class UsuarioExterno extends Model
{
    /** @use HasFactory<UsuarioExternoFactory> */
    use HasFactory;

    use PreventsForceDeleteWithDependents;
    use SoftDeletes;

    protected static array $forceDeleteGuardedRelations = ['notasSalida', 'notasRecepcion'];

    protected $table = 'usuarios_externos';

    protected $fillable = [
        'nombre',
        'carnet',
        'empresa',
        'cargo',
    ];

    public function notasSalida(): HasMany
    {
        return $this->hasMany(NotaSalidaEquipo::class, 'usuario_externo_id');
    }

    public function notasRecepcion(): HasMany
    {
        return $this->hasMany(NotaRecepcionEquipo::class, 'usuarios_externo_id');
    }
}
