<?php

namespace App\Support\Auditoria;

use App\Enums\DominioActividad;
use App\Enums\EventoSeguridad;
use Illuminate\Support\Str;

/**
 * Traduce a español los valores crudos de `activity_log`. Fuente única para la
 * tabla del panel y para la exportación, que deben coincidir.
 */
class EtiquetasActividad
{
    public static function dominio(?string $logName): string
    {
        if (blank($logName)) {
            return '-';
        }

        return DominioActividad::tryFrom($logName)?->getLabel()
            ?? Str::headline($logName);
    }

    /**
     * Cubre tanto los eventos de seguridad como los del ciclo de vida del
     * modelo. Sin traducción devuelve el nombre en formato de título, nunca la
     * clave cruda de `__()`.
     */
    public static function evento(?string $event): string
    {
        if (blank($event)) {
            return '-';
        }

        if ($evento = EventoSeguridad::tryFrom($event)) {
            return $evento->getLabel();
        }

        $clave = 'activitylog::action.event.'.$event;

        return trans()->has($clave)
            ? Str::ucfirst(__($clave))
            : Str::headline($event);
    }

    public static function colorDeEvento(?string $event): string
    {
        if ($evento = EventoSeguridad::tryFrom((string) $event)) {
            return $evento->getColor();
        }

        return match ($event) {
            'created' => 'success',
            'updated' => 'warning',
            'deleted' => 'danger',
            'restored' => 'info',
            default => 'gray',
        };
    }

    public static function resultado(?string $event): string
    {
        return EventoSeguridad::tryFrom((string) $event)?->resultado()->getLabel() ?? '';
    }

    /**
     * Nombre legible de un campo o propiedad, según `lang/es/activity.php`.
     */
    public static function campo(string $clave): string
    {
        $traduccion = 'activity.keys.'.$clave;

        return trans()->has($traduccion)
            ? __($traduccion)
            : Str::headline($clave);
    }
}
