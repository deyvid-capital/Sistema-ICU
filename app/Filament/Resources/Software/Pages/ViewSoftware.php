<?php

namespace App\Filament\Resources\Software\Pages;

use App\Filament\Resources\Software\SoftwareResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSoftware extends ViewRecord
{
    protected static string $resource = SoftwareResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
