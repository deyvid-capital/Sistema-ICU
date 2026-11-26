<?php

namespace App\Filament\Resources\PersonalExternoSoporteTecnicos\Pages;

use App\Filament\Resources\PersonalExternoSoporteTecnicos\PersonalExternoSoporteTecnicoResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPersonalExternoSoporteTecnico extends EditRecord
{
    protected static string $resource = PersonalExternoSoporteTecnicoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
