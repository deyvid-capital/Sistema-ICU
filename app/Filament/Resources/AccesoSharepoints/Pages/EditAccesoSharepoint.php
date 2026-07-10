<?php

namespace App\Filament\Resources\AccesoSharepoints\Pages;

use App\Filament\Resources\AccesoSharepoints\AccesoSharepointResource;
use App\Models\AccesoSharepoint;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditAccesoSharepoint extends EditRecord
{
    protected static string $resource = AccesoSharepointResource::class;

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Asignación actualizada')
            ->body('Los datos del acceso a SharePoint se guardaron correctamente.');
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('Ver'),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (AccesoSharepoint $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Asignación eliminada correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (AccesoSharepoint $record): bool => $record->trashed())
                    ->successNotificationTitle('Asignación eliminada permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (AccesoSharepoint $record): bool => $record->trashed())
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
