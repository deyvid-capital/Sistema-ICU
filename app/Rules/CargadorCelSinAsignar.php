<?php

namespace App\Rules;

use App\Models\AsigCargadorCel;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Un cargador de celular solo puede estar asignado a un celular a la vez.
 * Las asignaciones eliminadas no cuentan.
 */
class CargadorCelSinAsignar implements ValidationRule
{
    public const MENSAJE = 'El cargador ya está asignado a un celular. Elimine esa asignación antes de usarlo en otro.';

    /**
     * @param  AsigCargadorCel|null  $asignacionActual  La asignación que se está editando, para no contarla.
     */
    public function __construct(private ?AsigCargadorCel $asignacionActual = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        $yaAsignado = AsigCargadorCel::query()
            ->where('cargador_serial', $value)
            ->when(
                $this->asignacionActual !== null,
                fn ($consulta) => $consulta->whereKeyNot($this->asignacionActual->getKey()),
            )
            ->exists();

        if ($yaAsignado) {
            $fail(self::MENSAJE);
        }
    }
}
