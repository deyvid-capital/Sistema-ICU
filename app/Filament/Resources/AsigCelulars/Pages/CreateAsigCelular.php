<?php

namespace App\Filament\Resources\AsigCelulars\Pages;

use App\Filament\Resources\AsigCelulars\AsigCelularResource;
use App\Rules\CelularDisponibleParaAsignacion;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateAsigCelular extends CreateRecord
{
    protected static string $resource = AsigCelularResource::class;

    /**
     * Además del error bajo el campo, avisa arriba que el celular ya está asignado.
     */
    protected function onValidationError(ValidationException $exception): void
    {
        parent::onValidationError($exception);

        if (in_array(CelularDisponibleParaAsignacion::MENSAJE, $exception->validator->errors()->all(), true)) {
            Notification::make()
                ->danger()
                ->title('No se puede asignar el celular')
                ->body(CelularDisponibleParaAsignacion::MENSAJE)
                ->send();
        }
    }
}
