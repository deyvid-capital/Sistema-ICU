<?php

namespace App\Filament\Resources\SoftwareUsuarios\Pages;

use App\Filament\Resources\SoftwareUsuarios\SoftwareUsuarioResource;
use App\Models\SoftwareUsuario;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSoftwareUsuario extends CreateRecord
{
    protected static string $resource = SoftwareUsuarioResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeCreate(): void
    {
        $alreadyAssigned = SoftwareUsuario::query()
            ->where('usuario_id', $this->data['usuario_id'])
            ->where('id_software', $this->data['id_software'])
            ->exists();

        if ($alreadyAssigned) {
            Notification::make()
                ->warning()
                ->title('Software ya asignado')
                ->body('Este usuario ya tiene asignado el software seleccionado.')
                ->send();

            $this->halt();
        }
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Asignacion registrada')
            ->body('El software fue asignado correctamente al usuario.');
    }
}
