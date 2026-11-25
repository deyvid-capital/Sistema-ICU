<?php

namespace App\Filament\Resources\SoftwareCargos\Pages;

use App\Filament\Resources\SoftwareCargos\SoftwareCargoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSoftwareCargo extends ViewRecord
{
    protected static string $resource = SoftwareCargoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
