<?php

namespace App\Services\Pdf;

use App\Enums\CelularFormularioPdfTipo;
use App\Exceptions\CelularFormularioPdfException;
use App\Models\EntregaCelular;
use App\Models\RecepcionCelular;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CelularFormularioPdfValidator
{
    /**
     * @return list<string>
     *
     * @throws CelularFormularioPdfException
     */
    public function validate(Model $record, CelularFormularioPdfTipo $tipo): array
    {
        $errors = match ($tipo) {
            CelularFormularioPdfTipo::Entrega => $this->validateEntrega($this->asEntrega($record)),
            CelularFormularioPdfTipo::Recepcion => $this->validateRecepcion($this->asRecepcion($record)),
        };

        if ($errors !== []) {
            throw CelularFormularioPdfException::validationFailed($errors);
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    private function validateEntrega(EntregaCelular $record): array
    {
        $errors = $this->baseDocumentErrors($record);

        $asignacion = $record->asignacion;

        if ($asignacion === null || $this->isTrashed($asignacion)) {
            $errors[] = 'La asignación asociada no existe o está eliminada.';

            return $errors;
        }

        if ($asignacion->usuario === null || $this->isTrashed($asignacion->usuario)) {
            $errors[] = 'El usuario de la asignación no existe o está eliminado.';
        }

        if ($asignacion->celular === null || $this->isTrashed($asignacion->celular)) {
            $errors[] = 'El celular de la asignación no existe o está eliminado.';
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    private function validateRecepcion(RecepcionCelular $record): array
    {
        $errors = $this->baseDocumentErrors($record);

        $asignacion = $record->asignacion;

        if ($asignacion === null || $this->isTrashed($asignacion)) {
            $errors[] = 'La asignación asociada no existe o está eliminada.';

            return $errors;
        }

        if ($asignacion->usuario === null || $this->isTrashed($asignacion->usuario)) {
            $errors[] = 'El usuario de la asignación no existe o está eliminado.';
        }

        if ($asignacion->celular === null || $this->isTrashed($asignacion->celular)) {
            $errors[] = 'El celular de la asignación no existe o está eliminado.';
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    private function baseDocumentErrors(Model $record): array
    {
        $errors = [];

        if ($this->isTrashed($record)) {
            $errors[] = 'El registro está eliminado.';
        }

        if (blank($record->getAttribute('numero'))) {
            $errors[] = 'El número del formulario es obligatorio.';
        }

        if (blank($record->getAttribute('fecha'))) {
            $errors[] = 'La fecha del formulario es obligatoria.';
        }

        return $errors;
    }

    private function isTrashed(Model $model): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model), true)
            && method_exists($model, 'trashed')
            && $model->trashed();
    }

    private function asEntrega(Model $record): EntregaCelular
    {
        if (! $record instanceof EntregaCelular) {
            throw new CelularFormularioPdfException('El registro no es una entrega de celular.');
        }

        return $record;
    }

    private function asRecepcion(Model $record): RecepcionCelular
    {
        if (! $record instanceof RecepcionCelular) {
            throw new CelularFormularioPdfException('El registro no es una recepción de celular.');
        }

        return $record;
    }
}
