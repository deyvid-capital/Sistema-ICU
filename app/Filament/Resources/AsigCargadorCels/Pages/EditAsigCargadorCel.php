<?php

namespace App\Filament\Resources\AsigCargadorCels\Pages;

use App\Filament\Resources\AsigCargadorCels\AsigCargadorCelResource;
use App\Models\AsigCargadorCel;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditAsigCargadorCel extends EditRecord
{
    protected static string $resource = AsigCargadorCelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()->label('Ver'),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (AsigCargadorCel $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Asignación de cargador de celular eliminada correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (AsigCargadorCel $record): bool => $record->trashed())
                    ->successNotificationTitle('Asignación de cargador de celular eliminada permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (AsigCargadorCel $record): bool => $record->trashed())
                    ->successNotificationTitle('Asignación de cargador de celular restaurada correctamente'),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
