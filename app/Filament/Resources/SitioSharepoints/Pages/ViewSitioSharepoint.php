<?php

namespace App\Filament\Resources\SitioSharepoints\Pages;

use App\Filament\Resources\SitioSharepoints\SitioSharepointResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSitioSharepoint extends ViewRecord
{
    protected static string $resource = SitioSharepointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
