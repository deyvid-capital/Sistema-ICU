<?php

namespace App\Filament\Resources\ReciboEquipos\Pages;

use App\Filament\Resources\ReciboEquipos\ReciboEquipoResource;
use App\Models\ReciboEquipo;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditReciboEquipo extends EditRecord
{
    protected static string $resource = ReciboEquipoResource::class;

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Recibo actualizado')
            ->body('Los datos del recibo de equipo se guardaron correctamente.');
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('Ver'),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (ReciboEquipo $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Recibo de equipo eliminado correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (ReciboEquipo $record): bool => $record->trashed())
                    ->successNotificationTitle('Recibo de equipo eliminado permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (ReciboEquipo $record): bool => $record->trashed())
                    ->successNotificationTitle('Recibo de equipo restaurado correctamente'),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
