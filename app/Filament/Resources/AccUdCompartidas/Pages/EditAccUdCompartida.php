<?php

namespace App\Filament\Resources\AccUdCompartidas\Pages;

use App\Filament\Resources\AccUdCompartidas\AccUdCompartidaResource;
use App\Models\AccUdCompartida;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditAccUdCompartida extends EditRecord
{
    protected static string $resource = AccUdCompartidaResource::class;

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Asignación actualizada')
            ->body('Los datos de la unidad compartida se guardaron correctamente.');
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('Ver'),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (AccUdCompartida $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Asignación eliminada correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (AccUdCompartida $record): bool => $record->trashed())
                    ->successNotificationTitle('Asignación eliminada permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (AccUdCompartida $record): bool => $record->trashed())
                    ->successNotificationTitle('Asignación restaurada correctamente'),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
