<?php

namespace App\Filament\Resources\Firmantes\Pages;

use App\Filament\Resources\Firmantes\FirmanteResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFirmante extends ViewRecord
{
    protected static string $resource = FirmanteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
