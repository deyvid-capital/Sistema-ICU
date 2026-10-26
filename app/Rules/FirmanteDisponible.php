<?php

namespace App\Rules;

use App\Services\Firma\CrearSolicitudFirma;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * El usuario elegido como firmante tiene una firma digital activa y vigente.
 */
class FirmanteDisponible implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        if (! CrearSolicitudFirma::firmantesDisponibles()->whereKey($value)->exists()) {
            $fail('Este usuario no tiene una firma digital activa y vigente.');
        }
    }
}
