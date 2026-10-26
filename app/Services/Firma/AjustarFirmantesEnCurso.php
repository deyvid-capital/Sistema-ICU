<?php

namespace App\Services\Firma;

use App\Enums\EstadoFirma;
use App\Exceptions\FirmaDigitalException;
use App\Models\SolicitudFirma;
use App\Models\SolicitudFirmaFirma;
use App\Models\SolicitudFirmaFirmante;
use App\Models\User;
use App\Notifications\Firma\FirmaSolicitadaNotification;
use Illuminate\Support\Facades\DB;

/**
 * Para que un envío no quede trabado: reemplazar a un firmante que aún no firmó
 * (de viaje, de baja...) o quitar el orden obligatorio.
 */
class AjustarFirmantesEnCurso
{
    /**
     * El nuevo firmante hereda el lugar y los recuadros del anterior.
     *
     * @throws FirmaDigitalException
     */
    public function reemplazar(SolicitudFirmaFirmante $firmante, User $nuevo): void
    {
        $solicitud = $firmante->solicitud;

        if (! $solicitud->estaEnCurso()) {
            throw FirmaDigitalException::solicitudNoEnCurso();
        }

        if ($firmante->firmas()->where('estado', '!=', EstadoFirma::Pendiente)->exists()) {
            throw FirmaDigitalException::firmanteYaFirmo((string) $firmante->usuario?->name);
        }

        if ($solicitud->firmantes()->where('usuario_id', $nuevo->getKey())->exists()) {
            throw FirmaDigitalException::firmanteYaIncluido($nuevo->name);
        }

        if (! CrearSolicitudFirma::firmantesDisponibles()->whereKey($nuevo->getKey())->exists()) {
            throw FirmaDigitalException::firmanteSinFirmaActiva($nuevo->name);
        }

        DB::transaction(fn () => $firmante->update(['usuario_id' => $nuevo->getKey(), 'ultimo_recordatorio_at' => null]));

        if ($firmante->firmas()->with(['documento.solicitud', 'firmante'])->get()->contains(fn (SolicitudFirmaFirma $firma): bool => $firma->leToca())) {
            $nuevo->notify(new FirmaSolicitadaNotification($solicitud));
        }
    }

    /**
     * Pasa a orden libre y avisa a quienes recién ahora pueden firmar.
     *
     * @throws FirmaDigitalException
     */
    public function quitarOrden(SolicitudFirma $solicitud): void
    {
        if (! $solicitud->estaEnCurso()) {
            throw FirmaDigitalException::solicitudNoEnCurso();
        }

        $pendientes = SolicitudFirmaFirma::query()
            ->where('estado', EstadoFirma::Pendiente)
            ->whereHas('documento', fn ($query) => $query->where('solicitud_firma_id', $solicitud->id))
            ->with(['documento.solicitud', 'firmante.usuario'])
            ->get();

        $esperaban = $pendientes->reject(fn (SolicitudFirmaFirma $firma): bool => $firma->leToca());

        $solicitud->update(['orden_obligatorio' => false]);

        FirmarDocumento::avisarSiguientes(
            $solicitud,
            $esperaban->map(fn (SolicitudFirmaFirma $firma): ?User => $firma->firmante->usuario)->filter()->values(),
        );
    }
}
