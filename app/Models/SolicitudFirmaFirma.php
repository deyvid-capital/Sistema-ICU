<?php

namespace App\Models;

use App\Enums\EstadoFirma;
use App\Models\Concerns\LogsIcuActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Recuadro de un firmante en un documento: posición en puntos PDF (origen
 * abajo-izquierda) y estado de la firma.
 */
class SolicitudFirmaFirma extends Model
{
    use LogsIcuActivity;

    protected $table = 'solicitud_firma_firmas';

    protected $fillable = [
        'documento_id',
        'firmante_id',
        'campo',
        'pagina',
        'x',
        'y',
        'ancho',
        'alto',
        'estado',
        'certificado_firma_id',
        'firmado_at',
        'motivo_rechazo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoFirma::class,
            'pagina' => 'integer',
            'x' => 'float',
            'y' => 'float',
            'ancho' => 'float',
            'alto' => 'float',
            'firmado_at' => 'datetime',
        ];
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(SolicitudFirmaDocumento::class, 'documento_id');
    }

    public function firmante(): BelongsTo
    {
        return $this->belongsTo(SolicitudFirmaFirmante::class, 'firmante_id');
    }

    public function certificado(): BelongsTo
    {
        return $this->belongsTo(CertificadoFirma::class, 'certificado_firma_id')->withTrashed();
    }

    public function estaPendiente(): bool
    {
        return $this->estado === EstadoFirma::Pendiente;
    }

    /**
     * Sin orden obligatorio siempre le toca. Con orden, el turno es por documento:
     * firma cuando ya firmaron (o rechazaron) los anteriores en ese documento.
     */
    public function leToca(): bool
    {
        return ! $this->documento->solicitud->orden_obligatorio || $this->anteriorPendiente() === null;
    }

    /**
     * Primer firmante anterior (según el orden) que aún no firma este documento.
     */
    public function anteriorPendiente(): ?self
    {
        return self::query()
            ->where('documento_id', $this->documento_id)
            ->where('estado', EstadoFirma::Pendiente)
            ->whereHas('firmante', fn ($query) => $query->where('orden', '<', $this->firmante->orden))
            ->with('firmante.usuario')
            ->get()
            ->sortBy(fn (self $firma): int => $firma->firmante->orden)
            ->first();
    }
}
