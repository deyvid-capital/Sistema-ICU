<?php

namespace App\Filament\Resources\RecursoVpns\Pages;

use App\Filament\Resources\RecursoVpns\RecursoVpnResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRecursoVpns extends ManageRecords
{
    protected static string $resource = RecursoVpnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo recurso VPN')
                ->modalHeading('Registrar recurso VPN')
                ->createAnother(false)
                ->successNotificationTitle('Recurso VPN registrado correctamente'),
        ];
    }
}
