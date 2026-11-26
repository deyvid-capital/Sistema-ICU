<?php

namespace App\Filament\Resources\UsuariosExternos\Pages;

use App\Filament\Resources\UsuariosExternos\UsuarioExternoResource;
use App\Models\UsuarioExterno;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUsuarioExterno extends CreateRecord
{
    protected static string $resource = UsuarioExternoResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * El número del formulario se asigna aquí, en orden y dentro de una transacción.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return UsuarioExterno::crearNumerado($data);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title("Usuario externo registrado con el N° {$this->record->numero}")
            ->body($this->record->fecha_fin !== null
                ? 'Se avisará a los administradores el '.$this->record->fecha_fin->format('d/m/Y').'.'
                : 'El acceso no tiene fecha fin, así que no hay aviso programado.');
    }
}
