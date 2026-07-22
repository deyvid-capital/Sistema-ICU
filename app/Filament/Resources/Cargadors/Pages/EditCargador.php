<?php

namespace App\Filament\Resources\Cargadors\Pages;

use App\Filament\Resources\Cargadors\CargadorResource;
use App\Models\Cargador;
use App\Models\AsignacionCargador;
use App\Enums\EstadoCargador;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditCargador extends EditRecord
{
    protected static string $resource = CargadorResource::class;

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Cargador actualizado')
            ->body('Los datos del cargador se guardaron correctamente.');
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()->label('Ver'),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (Cargador $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Cargador eliminado correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (Cargador $record): bool => $record->trashed())
                    ->successNotificationTitle('Cargador eliminado permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (Cargador $record): bool => $record->trashed())
                    ->successNotificationTitle('Cargador restaurado correctamente'),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
