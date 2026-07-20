<?php

namespace App\Filament\Resources\Firmantes\Pages;

use App\Filament\Resources\Firmantes\FirmanteResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditFirmante extends EditRecord
{
    protected static string $resource = FirmanteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
