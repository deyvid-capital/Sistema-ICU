<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Usuario creado correctamente')
            ->body('El usuario ha sido creado correctamente.');
    }

    protected function afterCreate(): void
    {
        $this->record->movimientos()->create([
            'tipo' => 'Alta',
            'fecha' => now(),
            'cargo_id' => $this->record->id_cargo,
        ]);
    }
}
