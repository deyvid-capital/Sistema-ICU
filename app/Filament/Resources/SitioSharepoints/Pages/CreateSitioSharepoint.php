<?php

namespace App\Filament\Resources\SitioSharepoints\Pages;

use App\Filament\Resources\SitioSharepoints\SitioSharepointResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSitioSharepoint extends CreateRecord
{
    protected static string $resource = SitioSharepointResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Sitio registrado')
            ->body('El sitio SharePoint fue creado correctamente.');
    }
}
