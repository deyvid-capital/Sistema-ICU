<?php

namespace App\Filament\Resources\AccesoDazas\Pages;

use App\Filament\Resources\AccesoDazas\AccesoDazaResource;
use App\Models\AccesoDaza;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditAccesoDaza extends EditRecord
{
    protected static string $resource = AccesoDazaResource::class;

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Asignación actualizada')
            ->body('Los datos del acceso DAZA se guardaron correctamente.');
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('Ver'),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (AccesoDaza $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Asignación eliminada correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (AccesoDaza $record): bool => $record->trashed())
                    ->successNotificationTitle('Asignación eliminada permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (AccesoDaza $record): bool => $record->trashed())
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
