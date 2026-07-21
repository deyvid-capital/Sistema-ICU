<?php

namespace App\Services\Pdf;

use App\Enums\EquipoFormularioPdfTipo;
use App\Exceptions\EquipoFormularioPdfException;
use App\Models\EntregaEquipo;
use App\Models\NotaRecepcionEquipo;
use App\Models\NotaSalidaEquipo;
use App\Models\RecepcionEquipo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EquipoFormularioPdfValidator
{
    /**
     * @return list<string>
     *
     * @throws EquipoFormularioPdfException
     */
    public function validate(Model $record, EquipoFormularioPdfTipo $tipo): array
    {
        $errors = match ($tipo) {
            EquipoFormularioPdfTipo::Entrega => $this->validateEntrega($this->asEntrega($record)),
            EquipoFormularioPdfTipo::Recepcion => $this->validateRecepcion($this->asRecepcion($record)),
            EquipoFormularioPdfTipo::NotaSalida => $this->validateNotaSalida($this->asNotaSalida($record)),
            EquipoFormularioPdfTipo::NotaRecepcion => $this->validateNotaRecepcion($this->asNotaRecepcion($record)),
        };

        if ($errors !== []) {
            throw EquipoFormularioPdfException::validationFailed($errors);
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    private function validateEntrega(EntregaEquipo $record): array
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

        if ($asignacion->equipo === null || $this->isTrashed($asignacion->equipo)) {
            $errors[] = 'El equipo de la asignación no existe o está eliminado.';
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    private function validateRecepcion(RecepcionEquipo $record): array
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

        if ($asignacion->equipo === null || $this->isTrashed($asignacion->equipo)) {
            $errors[] = 'El equipo de la asignación no existe o está eliminado.';
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    private function validateNotaSalida(NotaSalidaEquipo $record): array
    {
        $errors = $this->baseDocumentErrors($record);

        if ($record->equipo === null || $this->isTrashed($record->equipo)) {
            $errors[] = 'El equipo no existe o está eliminado.';
        }

        if ($record->usuarioExterno === null || $this->isTrashed($record->usuarioExterno)) {
            $errors[] = 'El usuario externo no existe o está eliminado.';
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    private function validateNotaRecepcion(NotaRecepcionEquipo $record): array
    {
        $errors = $this->baseDocumentErrors($record);

        if ($record->equipo === null || $this->isTrashed($record->equipo)) {
            $errors[] = 'El equipo no existe o está eliminado.';
        }

        if ($record->usuarioExterno === null || $this->isTrashed($record->usuarioExterno)) {
            $errors[] = 'El usuario externo no existe o está eliminado.';
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

    private function asEntrega(Model $record): EntregaEquipo
    {
        if (! $record instanceof EntregaEquipo) {
            throw new EquipoFormularioPdfException('El registro no es una entrega de equipo.');
        }

        return $record;
    }

    private function asRecepcion(Model $record): RecepcionEquipo
    {
        if (! $record instanceof RecepcionEquipo) {
            throw new EquipoFormularioPdfException('El registro no es una recepción de equipo.');
        }

        return $record;
    }

    private function asNotaSalida(Model $record): NotaSalidaEquipo
    {
        if (! $record instanceof NotaSalidaEquipo) {
            throw new EquipoFormularioPdfException('El registro no es una nota de salida.');
        }

        return $record;
    }

    private function asNotaRecepcion(Model $record): NotaRecepcionEquipo
    {
        if (! $record instanceof NotaRecepcionEquipo) {
            throw new EquipoFormularioPdfException('El registro no es una nota de recepción.');
        }

        return $record;
    }
}
