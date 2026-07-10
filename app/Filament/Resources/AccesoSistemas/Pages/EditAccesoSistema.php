<?php

namespace App\Filament\Resources\AccesoSistemas\Pages;

use App\Filament\Resources\AccesoSistemas\AccesoSistemaResource;
use App\Models\AccesoSistema;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditAccesoSistema extends EditRecord
{
    protected static string $resource = AccesoSistemaResource::class;

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Asignación actualizada')
            ->body('Los datos del acceso al sistema se guardaron correctamente.');
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('Ver'),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (AccesoSistema $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Asignación eliminada correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (AccesoSistema $record): bool => $record->trashed())
                    ->successNotificationTitle('Asignación eliminada permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (AccesoSistema $record): bool => $record->trashed())
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
