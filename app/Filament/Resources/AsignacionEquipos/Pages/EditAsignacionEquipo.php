<?php

namespace App\Filament\Resources\AsignacionEquipos\Pages;

use App\Filament\Resources\AsignacionEquipos\AsignacionEquipoResource;
use App\Models\AsignacionEquipo;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditAsignacionEquipo extends EditRecord
{
    protected static string $resource = AsignacionEquipoResource::class;

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Asignación actualizada')
            ->body('Los datos de la asignación de equipo se guardaron correctamente.');
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('Ver'),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (AsignacionEquipo $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Asignación de equipo eliminada correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (AsignacionEquipo $record): bool => $record->trashed())
                    ->successNotificationTitle('Asignación de equipo eliminada permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (AsignacionEquipo $record): bool => $record->trashed())
                    ->successNotificationTitle('Asignación de equipo restaurada correctamente'),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
