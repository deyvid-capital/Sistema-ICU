<?php

namespace App\Filament\Resources\Licencias\Pages;

use App\Filament\Resources\Licencias\LicenciaResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLicencia extends ViewRecord
{
    protected static string $resource = LicenciaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
