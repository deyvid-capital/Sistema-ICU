<?php

namespace App\Rules;

use App\Enums\EstadoEquipo;
use App\Models\AsignacionEquipo;
use App\Models\Equipo;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EquipoDisponibleParaAsignacion implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        $equipo = Equipo::query()->find($value);

        if (! $equipo) {
            return;
        }

        $estado = $equipo->estado instanceof EstadoEquipo
            ? $equipo->estado
            : EstadoEquipo::tryFrom((string) $equipo->estado);

        if ($estado === null || ! $estado->isDisponibleParaAsignacion()) {
            $estadoLabel = $estado?->getLabel() ?? (string) $equipo->getRawOriginal('estado');
            $fail("El equipo no está disponible para asignar. Estado actual: {$estadoLabel}.");

            return;
        }

        $asignacionActiva = AsignacionEquipo::query()
            ->where('service_tag', $value)
            ->whereNull('fecha_fin')
            ->exists();

        if ($asignacionActiva) {
            $fail('El equipo tiene una asignación activa sin fecha de fin. Ciérrela antes de crear una nueva.');
        }
    }
}
