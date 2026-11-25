<?php

namespace App\Filament\Resources\SoftwareAreas\Pages;

use App\Filament\Resources\SoftwareAreas\SoftwareAreaResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSoftwareArea extends ViewRecord
{
    protected static string $resource = SoftwareAreaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
