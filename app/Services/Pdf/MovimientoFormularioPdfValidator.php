<?php

namespace App\Services\Pdf;

use App\Enums\MovimientoFormularioPdfTipo;
use App\Exceptions\MovimientoFormularioPdfException;
use App\Models\Movimiento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MovimientoFormularioPdfValidator
{
    /**
     * @return list<string>
     *
     * @throws MovimientoFormularioPdfException
     */
    public function validate(Model $record, MovimientoFormularioPdfTipo $tipo): array
    {
        $movimiento = $this->asMovimiento($record);
        $errors = $this->baseErrors($movimiento);

        if ($errors !== []) {
            throw MovimientoFormularioPdfException::validationFailed($errors);
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    private function baseErrors(Movimiento $movimiento): array
    {
        $errors = [];

        if ($this->isTrashed($movimiento)) {
            $errors[] = 'El movimiento está eliminado.';
        }

        if (blank($movimiento->tipo)) {
            $errors[] = 'El tipo del movimiento es obligatorio.';
        }

        if (blank($movimiento->fecha)) {
            $errors[] = 'La fecha del movimiento es obligatoria.';
        }

        if ($movimiento->usuario === null || $this->isTrashed($movimiento->usuario)) {
            $errors[] = 'El usuario del movimiento no existe o está eliminado.';
        }

        return $errors;
    }

    private function isTrashed(Model $model): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model), true)
            && method_exists($model, 'trashed')
            && $model->trashed();
    }

    private function asMovimiento(Model $record): Movimiento
    {
        if (! $record instanceof Movimiento) {
            throw new MovimientoFormularioPdfException('El registro no es un movimiento.');
        }

        return $record;
    }
}
