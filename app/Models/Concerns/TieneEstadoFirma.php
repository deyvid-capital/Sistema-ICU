<?php

namespace App\Models\Concerns;

use App\Enums\EstadoFirma;
use App\Enums\EstadoFirmaOrigen;
use App\Models\SolicitudFirmaDocumento;
use App\Models\SolicitudFirmaFirma;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Estado de firma digital de un PDF de origen (PdfEquipo, PdfCelular, PdfMovimiento).
 * Ver Docs/firma-digital/09-documentos-firmados-en-origen.md.
 *
 * `estado_firma` es fillable para que cada cambio quede en el historial de actividad.
 *
 * @property EstadoFirmaOrigen $estado_firma
 */
trait TieneEstadoFirma
{
    public function initializeTieneEstadoFirma(): void
    {
        $this->mergeFillable(['estado_firma', 'firmado_at', 'firma_documento_id', 'hash_sha256']);
        $this->mergeCasts([
            'estado_firma' => EstadoFirmaOrigen::class,
            'firmado_at' => 'datetime',
        ]);
        $this->attributes['estado_firma'] ??= EstadoFirmaOrigen::SinFirma->value;
    }

    /**
     * Documento del último envío que lo firmó o lo está firmando.
     */
    public function documentoFirma(): BelongsTo
    {
        return $this->belongsTo(SolicitudFirmaDocumento::class, 'firma_documento_id');
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeFirmables(Builder $query): void
    {
        $query->whereIn('estado_firma', EstadoFirmaOrigen::firmables());
    }

    public function esFirmable(): bool
    {
        return $this->estado_firma->esFirmable();
    }

    public function estaFirmado(): bool
    {
        return $this->estado_firma->estaFirmado();
    }

    /**
     * Firmas hechas y total del envío en curso o del último que lo firmó.
     *
     * @return array{firmadas: int, total: int}|null
     */
    public function progresoFirma(): ?array
    {
        $documento = $this->documentoFirma;

        if ($documento === null) {
            return null;
        }

        return [
            'firmadas' => $documento->firmas()->where('estado', EstadoFirma::Firmado)->count(),
            'total' => $documento->firmas()->count(),
        ];
    }

    /**
     * Texto del badge: "En firma 1/3", "Firmado", etc.
     */
    public function etiquetaEstadoFirma(): string
    {
        $progreso = $this->estado_firma === EstadoFirmaOrigen::EnFirma ? $this->progresoFirma() : null;

        return $progreso === null
            ? $this->estado_firma->getLabel()
            : "{$this->estado_firma->getLabel()} {$progreso['firmadas']}/{$progreso['total']}";
    }

    /**
     * Quiénes lo firmaron digitalmente (para la advertencia al reemplazarlo).
     *
     * @return Collection<int, array{nombre: string, fecha: string}>
     */
    public function firmasDigitales(): Collection
    {
        if ($this->estado_firma !== EstadoFirmaOrigen::Firmado || $this->documentoFirma === null) {
            return collect();
        }

        return $this->documentoFirma->firmas()
            ->where('estado', EstadoFirma::Firmado)
            ->with('firmante.usuario')
            ->orderBy('firmado_at')
            ->get()
            ->map(fn (SolicitudFirmaFirma $firma): array => [
                'nombre' => (string) $firma->firmante->usuario?->name,
                'fecha' => $firma->firmado_at?->timezone(config('app.display_timezone'))->format('d/m/Y H:i') ?? '',
            ]);
    }

    public function marcarEnFirma(SolicitudFirmaDocumento $documento): void
    {
        $this->update([
            'estado_firma' => EstadoFirmaOrigen::EnFirma,
            'firma_documento_id' => $documento->getKey(),
        ]);
    }

    public function marcarFirmado(string $hash): void
    {
        $this->update([
            'estado_firma' => EstadoFirmaOrigen::Firmado,
            'firmado_at' => now(),
            'hash_sha256' => $hash,
        ]);
    }

    public function marcarFirmadoManual(string $hash): void
    {
        $this->update([
            'estado_firma' => EstadoFirmaOrigen::FirmadoManual,
            'firmado_at' => now(),
            'firma_documento_id' => null,
            'hash_sha256' => $hash,
        ]);
    }

    public function marcarRechazado(): void
    {
        $this->update(['estado_firma' => EstadoFirmaOrigen::Rechazado]);
    }

    /**
     * El archivo se reemplazó por uno sin firmas (regenerado o envío cancelado).
     */
    public function marcarSinFirma(): void
    {
        $this->update([
            'estado_firma' => EstadoFirmaOrigen::SinFirma,
            'firmado_at' => null,
            'firma_documento_id' => null,
            'hash_sha256' => null,
        ]);
    }
}
