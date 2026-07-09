<?php

namespace App\Filament\Resources\SoftwareExcs\Pages;

use App\Filament\Resources\SoftwareExcs\SoftwareExcResource;
use App\Models\SoftwareExc;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditSoftwareExc extends EditRecord
{
    protected static string $resource = SoftwareExcResource::class;

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Excepcion actualizada')
            ->body('Los cambios se guardaron correctamente.');
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('Ver'),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (SoftwareExc $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Excepcion eliminada correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (SoftwareExc $record): bool => $record->trashed())
                    ->successNotificationTitle('Excepcion eliminada permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (SoftwareExc $record): bool => $record->trashed())
                    ->successNotificationTitle('Excepcion restaurada correctamente'),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
