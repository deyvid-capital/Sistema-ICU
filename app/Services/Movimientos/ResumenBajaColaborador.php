<?php

namespace App\Services\Movimientos;

/**
 * Contadores de lo que la cascada de Baja modificó.
 */
final readonly class ResumenBajaColaborador
{
    public function __construct(
        public int $accesosDesactivados = 0,
        public int $asignacionesEquipoCerradas = 0,
        public int $asignacionesCelularCerradas = 0,
        public int $asignacionesCargadorCerradas = 0,
        public int $excepcionesSoftwareCerradas = 0,
    ) {}

    public function totalAsignaciones(): int
    {
        return $this->asignacionesEquipoCerradas
            + $this->asignacionesCelularCerradas
            + $this->asignacionesCargadorCerradas
            + $this->excepcionesSoftwareCerradas;
    }

    public function total(): int
    {
        return $this->accesosDesactivados + $this->totalAsignaciones();
    }

    public function huboCambios(): bool
    {
        return $this->total() > 0;
    }

    public function mensaje(): string
    {
        $partes = [];

        if ($this->accesosDesactivados > 0) {
            $partes[] = $this->accesosDesactivados === 1
                ? 'se desactivó 1 acceso'
                : "se desactivaron {$this->accesosDesactivados} accesos";
        }

        $asignaciones = $this->totalAsignaciones();

        if ($asignaciones > 0) {
            $partes[] = $asignaciones === 1
                ? 'se cerró 1 asignación'
                : "se cerraron {$asignaciones} asignaciones";
        }

        if ($partes === []) {
            return 'No había accesos ni asignaciones activas que dar de baja.';
        }

        return ucfirst(implode(' y ', $partes)).'.';
    }
}
