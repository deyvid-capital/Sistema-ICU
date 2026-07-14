<?php

namespace App\Filament\Resources\Equipos\Pages;

use App\Filament\Resources\Equipos\EquipoResource;
use App\Models\Equipo;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditEquipo extends EditRecord
{
    protected static string $resource = EquipoResource::class;

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Equipo actualizado')
            ->body('Los datos del equipo se guardaron correctamente.');
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()->label('Ver'),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (Equipo $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Equipo eliminado correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (Equipo $record): bool => $record->trashed())
                    ->successNotificationTitle('Equipo eliminado permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (Equipo $record): bool => $record->trashed())
                    ->successNotificationTitle('Equipo restaurado correctamente'),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
