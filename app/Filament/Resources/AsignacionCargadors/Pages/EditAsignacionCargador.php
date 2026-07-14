<?php

namespace App\Filament\Resources\AsignacionCargadors\Pages;

use App\Filament\Resources\AsignacionCargadors\AsignacionCargadorResource;
use App\Models\AsignacionCargador;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditAsignacionCargador extends EditRecord
{
    protected static string $resource = AsignacionCargadorResource::class;

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Asignación actualizada')
            ->body('Los datos de la asignación de cargador se guardaron correctamente.');
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('Ver'),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (AsignacionCargador $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Asignación de cargador eliminada correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (AsignacionCargador $record): bool => $record->trashed())
                    ->successNotificationTitle('Asignación de cargador eliminada permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (AsignacionCargador $record): bool => $record->trashed())
                    ->successNotificationTitle('Asignación de cargador restaurada correctamente'),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
