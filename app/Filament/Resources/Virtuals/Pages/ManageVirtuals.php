<?php

namespace App\Filament\Resources\Virtuals\Pages;

use App\Filament\Resources\Virtuals\VirtualResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageVirtuals extends ManageRecords
{
    protected static string $resource = VirtualResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nueva máquina virtual')
                ->modalHeading('Registrar máquina virtual')
                ->createAnother(false)
                ->successNotificationTitle('Máquina virtual registrada correctamente'),
        ];
    }
}
