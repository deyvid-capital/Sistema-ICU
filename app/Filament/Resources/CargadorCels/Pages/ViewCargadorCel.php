<?php

namespace App\Filament\Resources\CargadorCels\Pages;

use App\Filament\Resources\CargadorCels\CargadorCelResource;
use App\Models\CargadorCel;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCargadorCel extends ViewRecord
{
    protected static string $resource = CargadorCelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Editar cargador')
                ->visible(fn (CargadorCel $record): bool => ! $record->trashed()),
        ];
    }
}
