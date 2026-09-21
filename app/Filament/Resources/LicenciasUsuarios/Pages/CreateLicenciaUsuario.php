<?php

namespace App\Filament\Resources\LicenciasUsuarios\Pages;

use App\Filament\Resources\LicenciasUsuarios\LicenciaUsuarioResource;
use App\Models\Licencia;
use App\Services\Licencias\GestionarCupoLicencia;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateLicenciaUsuario extends CreateRecord
{
    protected static string $resource = LicenciaUsuarioResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeCreate(): void
    {
        if (! ($this->data['estado'] ?? false)) {
            return;
        }

        $licencia = Licencia::query()->find($this->data['licencia_id'] ?? null);

        if ($licencia === null) {
            return;
        }

        app(GestionarCupoLicencia::class)->haltarSiNoPuedeConsumir($licencia);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Asignación registrada')
            ->body('La licencia fue asignada al usuario correctamente.');
    }
}
