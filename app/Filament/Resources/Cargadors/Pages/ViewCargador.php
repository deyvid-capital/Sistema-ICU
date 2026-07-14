<?php

namespace App\Filament\Resources\Cargadors\Pages;

use App\Filament\Resources\Cargadors\CargadorResource;
use App\Models\Cargador;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCargador extends ViewRecord
{
    protected static string $resource = CargadorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Editar cargador')
                ->visible(fn (Cargador $record): bool => ! $record->trashed()),
        ];
    }
}
