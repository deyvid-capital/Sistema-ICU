<?php

namespace App\Services\Firma;

use App\Enums\EstadoDocumentoFirma;
use App\Enums\EstadoFirma;
use App\Enums\EventoSolicitudFirma;
use App\Models\SolicitudFirma;
use App\Models\SolicitudFirmaFirma;
use App\Models\SolicitudFirmaFirmante;
use App\Models\User;
use App\Notifications\Firma\FirmaSolicitadaNotification;
use App\Notifications\Firma\SolicitudFirmaNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Quién se entera de qué en un envío a firmar. Ver Docs/firma-digital/06-notificaciones.md.
 * Cada aviso respeta la preferencia "Firma digital" de quien lo recibe.
 */
class AvisosSolicitudFirma
{
    /**
     * Avisa a quienes ahora pueden firmar. Marca el aviso para que el primer
     * recordatorio cuente desde aquí y no desde el envío.
     *
     * @param  Collection<int, User|null>  $usuarios
     */
    public function pedirFirma(SolicitudFirma $solicitud, Collection $usuarios): void
    {
        $usuarios = $this->unicos($usuarios);

        if ($usuarios->isEmpty()) {
            return;
        }

        SolicitudFirmaFirmante::query()
            ->where('solicitud_firma_id', $solicitud->getKey())
            ->whereIn('usuario_id', $usuarios->map(fn (User $usuario): string => (string) $usuario->getKey())->all())
            ->update(['ultimo_recordatorio_at' => now()]);

        Notification::send($usuarios, new FirmaSolicitadaNotification($solicitud));
    }

    /**
     * Al creador, cuando alguien firma (uno o varios documentos de una vez). Si con
     * esa firma el envío terminó, basta con el aviso de "finalizado".
     *
     * @param  list<string>  $documentos
     */
    public function firmaRealizada(SolicitudFirma $solicitud, User $firmante, array $documentos): void
    {
        $solicitud->refresh();
        $creador = $solicitud->creador;

        if ($documentos === [] || ! $solicitud->estaEnCurso() || $creador === null || $creador->is($firmante)) {
            return;
        }

        $firmas = SolicitudFirmaFirma::query()
            ->whereHas('documento', fn ($query) => $query->where('solicitud_firma_id', $solicitud->getKey()));
        $total = (clone $firmas)->count();
        $hechas = (clone $firmas)->where('estado', EstadoFirma::Firmado)->count();

        $cuerpo = count($documentos) === 1
            ? "Firmó «{$documentos[0]}»."
            : 'Firmó '.count($documentos).' documentos: '.implode(', ', $documentos).'.';

        $creador->notify(new SolicitudFirmaNotification(
            $solicitud,
            EventoSolicitudFirma::Realizada,
            "{$firmante->name} firmó en {$solicitud->codigo}",
            "{$cuerpo} Van {$hechas} de {$total} firmas.",
        ));
    }

    /**
     * Al creador y a todos los firmantes, cuando ya no queda ningún documento en curso.
     */
    public function finalizada(SolicitudFirma $solicitud): void
    {
        $completados = $solicitud->documentos()->where('estado', EstadoDocumentoFirma::Completado)->count();
        $rechazados = $solicitud->documentos()->where('estado', EstadoDocumentoFirma::Rechazado)->count();

        [$titulo, $cuerpo] = $rechazados === 0
            ? ["Envío completado: {$solicitud->codigo}", "Todos firmaron «{$solicitud->asunto}» ({$completados} documento(s)). Ya puedes descargarlos."]
            : ["Envío finalizado con rechazos: {$solicitud->codigo}", "«{$solicitud->asunto}» terminó con {$completados} documento(s) firmado(s) y {$rechazados} rechazado(s)."];

        Notification::send(
            $this->unicos($this->usuariosFirmantes($solicitud)->prepend($solicitud->creador)),
            new SolicitudFirmaNotification($solicitud, EventoSolicitudFirma::Finalizada, $titulo, $cuerpo),
        );
    }

    /**
     * Al creador y a los demás firmantes del documento rechazado.
     */
    public function rechazada(SolicitudFirmaFirma $firma, User $usuario, string $motivo): void
    {
        $documento = $firma->documento;
        $solicitud = $documento->solicitud;

        $destinatarios = $documento->firmas()
            ->with('firmante.usuario')
            ->get()
            ->map(fn (SolicitudFirmaFirma $otra): ?User => $otra->firmante->usuario)
            ->prepend($solicitud->creador)
            ->reject(fn (?User $destinatario): bool => $destinatario?->is($usuario) ?? true);

        Notification::send($this->unicos($destinatarios), new SolicitudFirmaNotification(
            $solicitud,
            EventoSolicitudFirma::Rechazada,
            "{$usuario->name} rechazó «{$documento->nombre}»",
            "Motivo: {$motivo}",
        ));
    }

    /**
     * A quienes todavía tenían firmas pendientes: ya no necesitan firmar.
     */
    public function cancelada(SolicitudFirma $solicitud, User $actor, string $motivo): void
    {
        $pendientes = SolicitudFirmaFirma::query()
            ->where('estado', EstadoFirma::Pendiente)
            ->whereHas('documento', fn ($query) => $query->where('solicitud_firma_id', $solicitud->getKey()))
            ->with('firmante.usuario')
            ->get()
            ->map(fn (SolicitudFirmaFirma $firma): ?User => $firma->firmante->usuario)
            ->reject(fn (?User $usuario): bool => $usuario?->is($actor) ?? true);

        Notification::send($this->unicos($pendientes), new SolicitudFirmaNotification(
            $solicitud,
            EventoSolicitudFirma::Cancelada,
            "Envío cancelado: {$solicitud->codigo}",
            "{$actor->name} canceló «{$solicitud->asunto}». Ya no necesitas firmarlo. Motivo: {$motivo}",
        ));
    }

    /**
     * Copia al creador cuando se recordó a firmantes con el plazo ya vencido.
     *
     * @param  Collection<int, User>  $pendientes
     */
    public function plazoVencido(SolicitudFirma $solicitud, Collection $pendientes): void
    {
        $creador = $solicitud->creador;

        if ($creador === null || $pendientes->isEmpty()) {
            return;
        }

        $creador->notify(new SolicitudFirmaNotification(
            $solicitud,
            EventoSolicitudFirma::Vencida,
            "Plazo vencido: {$solicitud->codigo}",
            'La fecha límite era el '.$solicitud->fecha_limite?->format('d/m/Y').'. Aún faltan firmar: '
                .$pendientes->pluck('name')->implode(', ').'. Se les envió un recordatorio.',
        ));
    }

    /**
     * @return Collection<int, User>
     */
    private function usuariosFirmantes(SolicitudFirma $solicitud): Collection
    {
        return $solicitud->firmantes()->with('usuario')->get()
            ->map(fn (SolicitudFirmaFirmante $firmante): ?User => $firmante->usuario);
    }

    /**
     * @param  Collection<int, User|null>  $usuarios
     * @return Collection<int, User>
     */
    private function unicos(Collection $usuarios): Collection
    {
        return $usuarios->filter()
            ->unique(fn (User $usuario): string => (string) $usuario->getKey())
            ->values();
    }
}
