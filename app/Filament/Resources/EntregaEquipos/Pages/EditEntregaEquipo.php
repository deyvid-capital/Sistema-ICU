<?php

namespace App\Filament\Resources\EntregaEquipos\Pages;

use App\Filament\Resources\EntregaEquipos\EntregaEquipoResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEntregaEquipo extends EditRecord
{
    protected static string $resource = EntregaEquipoResource::class;

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
