<?php

namespace App\Filament\Resources\AsigCargadorCels\Pages;

use App\Filament\Resources\AsigCargadorCels\AsigCargadorCelResource;
use App\Models\AsigCargadorCel;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAsigCargadorCel extends ViewRecord
{
    protected static string $resource = AsigCargadorCelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Editar asignación')
                ->visible(fn (AsigCargadorCel $record): bool => ! $record->trashed()),
        ];
    }
}
