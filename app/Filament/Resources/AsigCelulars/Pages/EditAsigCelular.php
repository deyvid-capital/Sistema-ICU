<?php

namespace App\Filament\Resources\AsigCelulars\Pages;

use App\Filament\Resources\AsigCelulars\AsigCelularResource;
use App\Models\AsigCelular;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditAsigCelular extends EditRecord
{
    protected static string $resource = AsigCelularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()->label('Ver'),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (AsigCelular $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Asignación de celular eliminada correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (AsigCelular $record): bool => $record->trashed())
                    ->successNotificationTitle('Asignación de celular eliminada permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (AsigCelular $record): bool => $record->trashed())
                    ->successNotificationTitle('Asignación de celular restaurada correctamente'),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
