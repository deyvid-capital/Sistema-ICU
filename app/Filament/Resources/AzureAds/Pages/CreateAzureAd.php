<?php

namespace App\Filament\Resources\AzureAds\Pages;

use App\Filament\Resources\AzureAds\AzureAdResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateAzureAd extends CreateRecord
{
    protected static string $resource = AzureAdResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Cuenta registrada')
            ->body('La cuenta Azure AD fue registrada correctamente.');
    }
}
