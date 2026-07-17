<?php

namespace App\Rules;

use App\Enums\EstadoEquipo;
use App\Models\AsignacionEquipo;
use App\Models\Equipo;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EquipoDisponibleParaAsignacion implements ValidationRule
{
    public function __construct(
        protected ?int $ignoreAsignacionId = null,
        protected bool $confirmarReasignacion = false,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        $equipo = Equipo::query()->find($value);

        if (! $equipo) {
            return;
        }

        if ($this->isCurrentActiveAssignment((string) $value)) {
            return;
        }

        $estado = $equipo->estado instanceof EstadoEquipo
            ? $equipo->estado
            : EstadoEquipo::tryFrom((string) $equipo->estado);

        if ($this->confirmarReasignacion) {
            // La reasignación ya fue confirmada: solo se rechazan estados
            // realmente bloqueados (mantenimiento, resguardo, baja).
            if ($estado?->estaBloqueado() ?? false) {
                $estadoLabel = $estado->getLabel();
                $fail("El equipo no está disponible para asignar. Estado actual: {$estadoLabel}.");
            }

            return;
        }

        if ($estado === null || ! $estado->isDisponibleParaAsignacion()) {
            $estadoLabel = $estado?->getLabel() ?? (string) $equipo->getRawOriginal('estado');
            $fail("El equipo no está disponible para asignar. Estado actual: {$estadoLabel}.");

            return;
        }

        $asignacionActiva = AsignacionEquipo::query()
            ->where('service_tag', $value)
            ->whereNull('fecha_fin')
            ->when(
                $this->ignoreAsignacionId !== null,
                fn ($query) => $query->whereKeyNot($this->ignoreAsignacionId),
            )
            ->exists();

        if ($asignacionActiva) {
            $fail('El equipo tiene una asignación activa sin fecha de fin. Ciérrela antes de crear una nueva.');
        }
    }

    private function isCurrentActiveAssignment(string $serviceTag): bool
    {
        if ($this->ignoreAsignacionId === null) {
            return false;
        }

        return AsignacionEquipo::query()
            ->whereKey($this->ignoreAsignacionId)
            ->where('service_tag', $serviceTag)
            ->whereNull('fecha_fin')
            ->exists();
    }
}
