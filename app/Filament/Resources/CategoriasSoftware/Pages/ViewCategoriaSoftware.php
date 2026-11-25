<?php

namespace App\Filament\Resources\CategoriasSoftware\Pages;

use App\Filament\Resources\CategoriasSoftware\CategoriaSoftwareResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCategoriaSoftware extends ViewRecord
{
    protected static string $resource = CategoriaSoftwareResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
