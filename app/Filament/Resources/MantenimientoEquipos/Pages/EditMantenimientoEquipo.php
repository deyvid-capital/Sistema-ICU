<?php

namespace App\Filament\Resources\MantenimientoEquipos\Pages;

use App\Filament\Resources\MantenimientoEquipos\MantenimientoEquipoResource;
use App\Models\MantenimientoEquipo;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditMantenimientoEquipo extends EditRecord
{
    protected static string $resource = MantenimientoEquipoResource::class;

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Mantenimiento actualizado')
            ->body('Los datos del mantenimiento de equipo se guardaron correctamente.');
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('Ver'),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (MantenimientoEquipo $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Mantenimiento de equipo eliminado correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (MantenimientoEquipo $record): bool => $record->trashed())
                    ->successNotificationTitle('Mantenimiento de equipo eliminado permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (MantenimientoEquipo $record): bool => $record->trashed())
                    ->successNotificationTitle('Mantenimiento de equipo restaurado correctamente'),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
