<?php

namespace App\Filament\Resources\AccesoVirtuals\Pages;

use App\Filament\Resources\AccesoVirtuals\AccesoVirtualResource;
use App\Models\AccesoVirtual;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditAccesoVirtual extends EditRecord
{
    protected static string $resource = AccesoVirtualResource::class;

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Asignación actualizada')
            ->body('Los datos del acceso virtual se guardaron correctamente.');
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('Ver'),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (AccesoVirtual $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Asignación eliminada correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (AccesoVirtual $record): bool => $record->trashed())
                    ->successNotificationTitle('Asignación eliminada permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (AccesoVirtual $record): bool => $record->trashed())
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
