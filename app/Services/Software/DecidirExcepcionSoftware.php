<?php

namespace App\Services\Software;

use App\Enums\EstadoAprobacionExcepcion;
use App\Models\SoftwareExc;
use App\Models\User;
use App\Support\Notifications\NotifyExcepcionSoftware;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DecidirExcepcionSoftware
{
    public function aprobar(SoftwareExc $excepcion, User $actor, ?string $comentario = null): SoftwareExc
    {
        return $this->decidir($excepcion, $actor, EstadoAprobacionExcepcion::Aprobada, $comentario);
    }

    public function rechazar(SoftwareExc $excepcion, User $actor, string $comentario): SoftwareExc
    {
        return $this->decidir($excepcion, $actor, EstadoAprobacionExcepcion::Rechazada, $comentario);
    }

    private function decidir(
        SoftwareExc $excepcion,
        User $actor,
        EstadoAprobacionExcepcion $estado,
        ?string $comentario,
    ): SoftwareExc {
        if ($excepcion->trashed()) {
            throw new InvalidArgumentException('No se puede decidir una excepción que está en la papelera.');
        }

        if (! $excepcion->estaPendiente()) {
            throw new InvalidArgumentException('Esta excepción ya no está pendiente de aprobación.');
        }

        if (! $excepcion->puedeDecidir($actor)) {
            throw new InvalidArgumentException('No tienes permiso para decidir esta excepción.');
        }

        $comentario = filled($comentario) ? trim($comentario) : null;
        $interviene = $excepcion->requiereNotaDeIntervencion($actor);

        if ($estado === EstadoAprobacionExcepcion::Rechazada && $comentario === null) {
            throw new InvalidArgumentException('El rechazo requiere una nota de respaldo.');
        }

        if ($interviene && $comentario === null) {
            throw new InvalidArgumentException('La intervención de Super Admin requiere una nota de respaldo.');
        }

        $excepcion = DB::transaction(function () use ($excepcion, $actor, $estado, $comentario): SoftwareExc {
            $excepcion->update([
                'estado_aprobacion' => $estado,
                'decidido_por_id' => $actor->getKey(),
                'decidido_at' => now(),
                'comentario_decision' => $comentario,
            ]);

            return $excepcion->fresh([
                'usuario',
                'software',
                'aprobador',
                'decididoPor',
            ]) ?? $excepcion;
        });

        NotifyExcepcionSoftware::decisionTomada($excepcion, $interviene);

        return $excepcion;
    }
}
