<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUsuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Persona que debe firmar un envío. `orden` solo se respeta si el envío exige orden.
 */
class SolicitudFirmaFirmante extends Model
{
    use BelongsToUsuario;

    protected $table = 'solicitud_firma_firmantes';

    protected $fillable = [
        'solicitud_firma_id',
        'usuario_id',
        'orden',
        'ultimo_recordatorio_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'ultimo_recordatorio_at' => 'datetime',
        ];
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudFirma::class, 'solicitud_firma_id');
    }

    public function firmas(): HasMany
    {
        return $this->hasMany(SolicitudFirmaFirma::class, 'firmante_id');
    }
}
