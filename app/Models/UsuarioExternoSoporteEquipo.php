<?php

namespace App\Models;

use App\Models\Concerns\LogsIcuActivity;
use App\Models\Concerns\PreventsForceDeleteWithDependents;
use Database\Factories\UsuarioExternoSoporteEquipoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Empresa externa (por ejemplo, una aseguradora como Fortaleza) a la que se
 * entregan equipos de la empresa para revisión en su servicio técnico.
 * La salida y la devolución quedan en las notas de salida y de recepción.
 */
class UsuarioExternoSoporteEquipo extends Model
{
    /** @use HasFactory<UsuarioExternoSoporteEquipoFactory> */
    use HasFactory;

    use LogsIcuActivity;
    use PreventsForceDeleteWithDependents;
    use SoftDeletes;

    protected static array $forceDeleteGuardedRelations = ['notasSalida', 'notasRecepcion'];

    protected $table = 'usuarios_externos_soporte_equipos';

    protected $fillable = [
        'nombre',
        'carnet',
        'empresa',
        'cargo',
    ];

    public function notasSalida(): HasMany
    {
        return $this->hasMany(NotaSalidaEquipo::class, 'usuario_externo_soporte_id');
    }

    public function notasRecepcion(): HasMany
    {
        return $this->hasMany(NotaRecepcionEquipo::class, 'usuario_externo_soporte_id');
    }
}
