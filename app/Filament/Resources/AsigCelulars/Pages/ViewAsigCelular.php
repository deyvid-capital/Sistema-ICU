<?php

namespace App\Filament\Resources\AsigCelulars\Pages;

use App\Filament\Resources\AsigCelulars\AsigCelularResource;
use App\Models\AsigCelular;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAsigCelular extends ViewRecord
{
    protected static string $resource = AsigCelularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Editar asignación')
                ->visible(fn (AsigCelular $record): bool => ! $record->trashed()),
        ];
    }
}
