<?php

namespace App\Filament\Resources\Movimientos\Pages;

use App\Filament\Resources\Movimientos\MovimientoResource;
use App\Models\Movimiento;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditMovimiento extends EditRecord
{
    protected static string $resource = MovimientoResource::class;

    protected function beforeSave(): void
    {
        $errores = Movimiento::erroresDeValidacion($this->data, $this->record->id, esEdicion: true);

        if ($errores !== []) {
            Notification::make()
                ->title('No se pudo actualizar el movimiento')
                ->body(implode(' ', $errores))
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function getSaveFormAction(): Action
    {
        // Acción "montada" (no submit) para que el modal de confirmación aparezca.
        return parent::getSaveFormAction()
            ->submit(null)
            ->action(fn () => $this->save())
            ->requiresConfirmation(fn (): bool => Movimiento::avisosDeConfirmacion($this->data, $this->record->id) !== [])
            ->modalHeading('Confirmar movimiento')
            ->modalDescription(fn (): string => implode(' ', Movimiento::avisosDeConfirmacion($this->data, $this->record->id)).' ¿Deseas continuar?')
            ->modalSubmitActionLabel('Sí, guardar');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Movimiento actualizado')
            ->body('Los datos del movimiento se guardaron correctamente.');
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('Ver'),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (Movimiento $record): bool => ! $record->trashed()
                                    && $record->id !== auth()->user()->id)
                    ->successNotificationTitle('Movimiento eliminado correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->successNotificationTitle('Movimiento eliminado permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->successNotificationTitle('Movimiento restaurado correctamente'),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
