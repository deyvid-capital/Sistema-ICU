<?php

namespace App\Filament\Resources\Software\Pages;

use App\Filament\Resources\Software\SoftwareResource;
use App\Models\SoftwareUsuario;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditSoftware extends EditRecord
{
    protected static string $resource = SoftwareResource::class;

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Software actualizado')
            ->body('Los cambios del software se guardaron correctamente.');
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('Ver'),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->requiresConfirmation()
                    ->successNotificationTitle('Software eliminado correctamente'),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }

    protected function afterSave(): void
    {
        if (! $this->record->estado) {
            $softwareUsuarios = SoftwareUsuario::where('id_software', $this->record->id_software)->where('estado', true)->get();

            foreach ($softwareUsuarios as $softwareUsuario) {
                $softwareUsuario->update(['estado' => false]);
            }
        }
    }
}
