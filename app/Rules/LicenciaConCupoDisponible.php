<?php

namespace App\Rules;

use App\Models\Licencia;
use App\Models\LicenciaUsuario;
use App\Services\Licencias\GestionarCupoLicencia;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

class LicenciaConCupoDisponible implements ValidationRule
{
    public function __construct(
        protected ?LicenciaUsuario $assignment = null,
        protected bool $consumes = true,
        protected ?Licencia $licencia = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->consumes) {
            return;
        }

        $licencia = $this->licencia;

        if ($licencia === null) {
            if (blank($value) || is_bool($value)) {
                return;
            }

            $licencia = Licencia::query()->find($value);
        }

        if ($licencia === null) {
            return;
        }

        try {
            app(GestionarCupoLicencia::class)->assertPuedeConsumir($licencia, $this->assignment);
        } catch (InvalidArgumentException $exception) {
            $fail($exception->getMessage());
        }
    }
}
