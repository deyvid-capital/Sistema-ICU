<?php

namespace App\Filament\Resources\AsignacionCargadors\Pages;

use App\Filament\Resources\AsignacionCargadors\AsignacionCargadorResource;
use App\Models\AsignacionCargador;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAsignacionCargador extends ViewRecord
{
    protected static string $resource = AsignacionCargadorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Editar asignación')
                ->visible(fn (AsignacionCargador $record): bool => ! $record->trashed()),
        ];
    }
}
