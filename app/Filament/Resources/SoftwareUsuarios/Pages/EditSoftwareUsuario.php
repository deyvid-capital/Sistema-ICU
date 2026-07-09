<?php

namespace App\Filament\Resources\SoftwareUsuarios\Pages;

use App\Filament\Resources\SoftwareUsuarios\SoftwareUsuarioResource;
use App\Models\SoftwareUsuario;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditSoftwareUsuario extends EditRecord
{
    protected static string $resource = SoftwareUsuarioResource::class;

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Asignacion actualizada')
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
                    ->visible(fn (SoftwareUsuario $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Asignacion eliminada correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (SoftwareUsuario $record): bool => $record->trashed())
                    ->successNotificationTitle('Asignacion eliminada permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (SoftwareUsuario $record): bool => $record->trashed())
                    ->successNotificationTitle('Asignacion restaurada correctamente'),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
