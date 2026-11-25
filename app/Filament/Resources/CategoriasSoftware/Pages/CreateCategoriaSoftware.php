<?php

namespace App\Filament\Resources\CategoriasSoftware\Pages;

use App\Filament\Resources\CategoriasSoftware\CategoriaSoftwareResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateCategoriaSoftware extends CreateRecord
{
    protected static string $resource = CategoriaSoftwareResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Categoría registrada')
            ->body('La categoría de software fue creada correctamente.');
    }
}
