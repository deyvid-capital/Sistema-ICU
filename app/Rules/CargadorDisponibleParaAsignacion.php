<?php

namespace App\Rules;

use App\Enums\EstadoCargador;
use App\Models\AsignacionCargador;
use App\Models\Cargador;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CargadorDisponibleParaAsignacion implements ValidationRule
{
    public function __construct(
        protected ?int $ignoreAsignacionId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        $cargador = Cargador::query()->find($value);

        if (! $cargador) {
            return;
        }

        if ($this->isCurrentActiveAssignment((string) $value)) {
            return;
        }

        $estado = $cargador->estado instanceof EstadoCargador
            ? $cargador->estado
            : EstadoCargador::tryFrom((string) $cargador->estado);

        if ($estado === null || ! $estado->isDisponibleParaAsignacion()) {
            $estadoLabel = $estado?->getLabel() ?? (string) $cargador->getRawOriginal('estado');
            $fail("El cargador no está disponible para asignar. Estado actual: {$estadoLabel}.");

            return;
        }

        $asignacionActiva = AsignacionCargador::query()
            ->where('serial_cargador', $value)
            ->whereNull('fecha_fin')
            ->when(
                $this->ignoreAsignacionId !== null,
                fn ($query) => $query->whereKeyNot($this->ignoreAsignacionId),
            )
            ->exists();

            if ($asignacionActiva) {
                $fail('El cargador tiene una asignación activa sin fecha de fin. Ciérrela antes de crear una nueva.');
        }
    }

    private function isCurrentActiveAssignment(string $serial): bool
    {
        if ($this->ignoreAsignacionId === null) {
            return false;
        }

        return AsignacionCargador::query()
            ->whereKey($this->ignoreAsignacionId)
            ->where('serial_cargador', $serial)
            ->whereNull('fecha_fin')
            ->exists();
    }
}
