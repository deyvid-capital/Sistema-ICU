<?php

namespace App\Filament\Resources\Movimientos\Pages;

use App\Filament\Resources\Movimientos\MovimientoResource;
use App\Models\Movimiento;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateMovimiento extends CreateRecord
{
    protected static string $resource = MovimientoResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeCreate(): void
    {
        $errores = Movimiento::erroresDeValidacion($this->data);

        if ($errores !== []) {
            Notification::make()
                ->title('No se pudo registrar el movimiento')
                ->body(implode(' ', $errores))
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function getCreateFormAction(): Action
    {
        // Convertimos el botón en una acción "montada" (no submit) para que el
        // modal de confirmación pueda mostrarse; los submit no soportan modal.
        return parent::getCreateFormAction()
            ->submit(null)
            ->action(fn () => $this->create())
            ->requiresConfirmation(fn (): bool => Movimiento::avisosDeConfirmacion($this->data) !== [])
            ->modalHeading('Confirmar movimiento')
            ->modalDescription(fn (): string => implode(' ', Movimiento::avisosDeConfirmacion($this->data)).' ¿Deseas continuar?')
            ->modalSubmitActionLabel('Sí, crear');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Movimiento registrado correctamente')
            ->body('El movimiento ha sido registrado correctamente.');
    }
}
