<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Equipo extends Model
{
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
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'inicio_garantia' => 'date',
            'venc_garantia' => 'date',
            'bloqueo_bios' => 'boolean',
            'etiquetado' => 'boolean',
            'usuario_admin' => 'boolean',
        ];
    }

    /**
     * Get the laptop model for the equipment.
     */
    public function modelo(): BelongsTo
    {
        return $this->belongsTo(ModeloLaptop::class, 'modelo_id');
    }

    /**
     * Get the processor for the equipment.
     */
    public function microprocesador(): BelongsTo
    {
        return $this->belongsTo(Microprocesador::class, 'microprocesador_id');
    }

    /**
     * Get the RAM for the equipment.
     */
    public function ram(): BelongsTo
    {
        return $this->belongsTo(MemoriaRam::class, 'ram_id');
    }

    /**
     * Get the GPU for the equipment.
     */
    public function gpu(): BelongsTo
    {
        return $this->belongsTo(TarjetaVideo::class, 'gpu_id');
    }

    /**
     * Get the assignments for the equipment.
     */
    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionEquipo::class, 'service_tag', 'service_tag');
    }

    /**
     * Get the charger assignment for the equipment.
     */
    public function asignacionCargador(): HasOne
    {
        return $this->hasOne(AsignacionCargador::class, 'service_tag', 'service_tag');
    }

    /**
     * Get the receipts for the equipment.
     */
    public function recibos(): HasMany
    {
        return $this->hasMany(ReciboEquipo::class, 'equipo_id', 'service_tag');
    }

    /**
     * Get the maintenance records for the equipment.
     */
    public function mantenimientos(): HasMany
    {
        return $this->hasMany(MantenimientoEquipo::class, 'service_tag', 'service_tag');
    }

    /**
     * Get the outbound notes for the equipment.
     */
    public function notasSalida(): HasMany
    {
        return $this->hasMany(NotaSalidaEquipo::class, 'equipo_id', 'service_tag');
    }

    /**
     * Get the inbound notes for the equipment.
     */
    public function notasRecepcion(): HasMany
    {
        return $this->hasMany(NotaRecepcionEquipo::class, 'equipo_id', 'service_tag');
    }
}
