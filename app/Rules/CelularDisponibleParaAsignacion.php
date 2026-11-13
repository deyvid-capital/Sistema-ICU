<?php

namespace App\Rules;

use App\Models\AsigCelular;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Un celular no puede tener dos asignaciones activas (sin fecha de fin) a la vez.
 */
class CelularDisponibleParaAsignacion implements ValidationRule
{
    public const MENSAJE = 'El celular tiene una asignación activa sin fecha de fin. Ciérrela antes de crear una nueva.';

    /**
     * @param  AsigCelular|null  $asignacionActual  La asignación que se está editando, para no contarla.
     */
    public function __construct(private ?AsigCelular $asignacionActual = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        $tieneOtraActiva = AsigCelular::query()
            ->where('celular_id', $value)
            ->whereNull('fecha_fin')
            ->when(
                $this->asignacionActual !== null,
                fn ($consulta) => $consulta->whereKeyNot($this->asignacionActual->getKey()),
            )
            ->exists();

        if ($tieneOtraActiva) {
            $fail(self::MENSAJE);
        }
    }
}
