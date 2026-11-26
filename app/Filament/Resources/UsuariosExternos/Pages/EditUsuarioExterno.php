<?php

namespace App\Filament\Resources\UsuariosExternos\Pages;

use App\Filament\Resources\Concerns\UsuarioExternoFormularioPdfActions;
use App\Filament\Resources\UsuariosExternos\UsuarioExternoResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditUsuarioExterno extends EditRecord
{
    protected static string $resource = UsuarioExternoResource::class;

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Usuario externo actualizado')
            ->body('Aviso de fecha fin: '.mb_lcfirst($this->record->estadoRecordatorio()).'.');
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ...UsuarioExternoFormularioPdfActions::make(),
                ViewAction::make()
                    ->label('Ver'),
                DeleteAction::make()
                    ->label('Eliminar'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar'),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
