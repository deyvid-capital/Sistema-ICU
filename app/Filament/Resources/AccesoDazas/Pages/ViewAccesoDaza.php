<?php

namespace App\Filament\Resources\AccesoDazas\Pages;

use App\Filament\Resources\AccesoDazas\AccesoDazaResource;
use App\Models\AccesoDaza;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAccesoDaza extends ViewRecord
{
    protected static string $resource = AccesoDazaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Editar asignación')
                ->visible(fn (AccesoDaza $record): bool => ! $record->trashed()),
        ];
    }
}
