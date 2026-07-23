<?php

namespace App\Filament\Resources\Celulares\Pages;

use App\Filament\Resources\Celulares\CelularResource;
use App\Models\Celular;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCelular extends ViewRecord
{
    protected static string $resource = CelularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Editar celular')
                ->visible(fn (Celular $record): bool => ! $record->trashed()),
        ];
    }
}
