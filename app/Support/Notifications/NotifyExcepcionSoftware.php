<?php

namespace App\Support\Notifications;

use App\Enums\EstadoAprobacionExcepcion;
use App\Filament\Resources\SoftwareExcs\SoftwareExcResource;
use App\Models\SoftwareExc;
use App\Models\User;
use App\Notifications\CriticalEventNotification;

/**
 * Avisos del flujo de aprobación de excepciones de software.
 *
 * Siempre van por campana (con enlace a la ficha) y por correo.
 */
class NotifyExcepcionSoftware
{
    public static function aprobadorAsignado(SoftwareExc $excepcion): void
    {
        $excepcion->load(['usuario', 'software', 'aprobador']);

        $aprobador = $excepcion->aprobador;

        if (! $aprobador instanceof User) {
            return;
        }

        $usuario = $excepcion->usuario?->name ?? (string) $excepcion->usuario_id;
        $software = $excepcion->software?->nombre_software ?? (string) $excepcion->software_id;

        static::enviar(
            $aprobador,
            'Excepción de software pendiente de tu aprobación',
            "Debes aprobar o rechazar la excepción de «{$software}» para {$usuario}.",
            $excepcion,
            'heroicon-o-shield-exclamation',
            'warning',
        );
    }

    public static function decisionTomada(SoftwareExc $excepcion, bool $intervinoSuperAdmin): void
    {
        $excepcion->loadMissing(['usuario', 'software', 'aprobador', 'decididoPor']);

        $estado = $excepcion->estado_aprobacion ?? EstadoAprobacionExcepcion::Pendiente;
        $usuario = $excepcion->usuario?->name ?? (string) $excepcion->usuario_id;
        $software = $excepcion->software?->nombre_software ?? (string) $excepcion->software_id;
        $actor = $excepcion->decididoPor?->name ?? 'Sistema';
        $verbo = $estado === EstadoAprobacionExcepcion::Aprobada ? 'aprobó' : 'rechazó';

        $title = $estado === EstadoAprobacionExcepcion::Aprobada
            ? 'Excepción de software aprobada'
            : 'Excepción de software rechazada';

        $body = "{$actor} {$verbo} la excepción de «{$software}» para {$usuario}.";

        if (filled($excepcion->comentario_decision)) {
            $body .= " Nota: {$excepcion->comentario_decision}";
        }

        NotifyAdmins::dispatch(
            title: $title,
            body: $body,
            url: static::url($excepcion),
            icon: $estado === EstadoAprobacionExcepcion::Aprobada
                ? 'heroicon-o-check-circle'
                : 'heroicon-o-x-circle',
            color: $estado === EstadoAprobacionExcepcion::Aprobada ? 'success' : 'danger',
            severity: CriticalEventNotification::SEVERIDAD_ALTA,
            actor: $excepcion->decididoPor,
        );

        if (! $intervinoSuperAdmin) {
            return;
        }

        $aprobador = $excepcion->aprobador;

        if (! $aprobador instanceof User) {
            return;
        }

        static::enviar(
            $aprobador,
            $title,
            "Super Admin intervino en tu lugar: {$body}",
            $excepcion,
            $estado === EstadoAprobacionExcepcion::Aprobada
                ? 'heroicon-o-check-circle'
                : 'heroicon-o-x-circle',
            $estado === EstadoAprobacionExcepcion::Aprobada ? 'success' : 'danger',
        );
    }

    private static function enviar(
        User $destinatario,
        string $title,
        string $body,
        SoftwareExc $excepcion,
        string $icon,
        string $color,
    ): void {
        $destinatario->notify(new CriticalEventNotification(
            title: $title,
            body: $body,
            url: static::url($excepcion),
            icon: $icon,
            color: $color,
            severity: CriticalEventNotification::SEVERIDAD_ALTA,
        ));
    }

    private static function url(SoftwareExc $excepcion): string
    {
        return SoftwareExcResource::getUrl('view', ['record' => $excepcion]);
    }
}
