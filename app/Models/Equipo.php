<?php

namespace App\Models;

use App\Enums\EstadoEquipo;
use App\Models\Concerns\PreventsForceDeleteWithDependents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipo extends Model
{
    use HasFactory;
    use PreventsForceDeleteWithDependents;
    use SoftDeletes;

    protected static array $forceDeleteGuardedRelations = [
        'asignaciones',
        'asignacionesCargador',
        'recibos',
        'mantenimientos',
        'notasSalida',
        'notasRecepcion',
    ];

    protected $primaryKey = 'service_tag';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'service_tag',
        'modelo_id',
        'microprocesador_id',
        'disco_duro',
        'ram_id',
        'gpu_id',
        'proveedor',
        'inicio_garantia',
        'venc_garantia',
        'nombre_equipo',
        'bloqueo_bios',
        'codigo_activo',
        'so',
        'licencia_so',
        'etiquetado',
        'usuario_admin',
        'litigation_hold',
        'bitlocker',
        'estado',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'inicio_garantia' => 'date',
            'venc_garantia' => 'date',
            'bloqueo_bios' => 'boolean',
            'etiquetado' => 'boolean',
            'usuario_admin' => 'boolean',
            'litigation_hold' => 'boolean',
            'bitlocker' => 'boolean',
            'estado' => EstadoEquipo::class,
        ];
    }

    public function asignacionActiva(): HasOne
    {
        return $this->hasOne(AsignacionEquipo::class, 'service_tag', 'service_tag')
            ->whereNull('fecha_fin')
            ->latest('fecha_inicio');
    }

    public function modelo(): BelongsTo
    {
        return $this->belongsTo(ModeloLaptop::class, 'modelo_id');
    }

    public function microprocesador(): BelongsTo
    {
        return $this->belongsTo(Microprocesador::class, 'microprocesador_id');
    }

    public function ram(): BelongsTo
    {
        return $this->belongsTo(MemoriaRam::class, 'ram_id');
    }

    public function gpu(): BelongsTo
    {
        return $this->belongsTo(TarjetaVideo::class, 'gpu_id');
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionEquipo::class, 'service_tag', 'service_tag');
    }

    public function asignacionCargador(): HasOne
    {
        return $this->hasOne(AsignacionCargador::class, 'service_tag', 'service_tag')
            ->whereNull('fecha_fin')
            ->latest('fecha_inicio');
    }

    public function asignacionesCargador(): HasMany
    {
        return $this->hasMany(AsignacionCargador::class, 'service_tag', 'service_tag');
    }

    public function recibos(): HasMany
    {
        return $this->hasMany(ReciboEquipo::class, 'equipo_id', 'service_tag');
    }

    public function mantenimientos(): HasMany
    {
        return $this->hasMany(MantenimientoEquipo::class, 'service_tag', 'service_tag');
    }

    public function notasSalida(): HasMany
    {
        return $this->hasMany(NotaSalidaEquipo::class, 'equipo_id', 'service_tag');
    }

    public function notasRecepcion(): HasMany
    {
        return $this->hasMany(NotaRecepcionEquipo::class, 'equipo_id', 'service_tag');
    }
}
