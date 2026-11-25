<?php

namespace App\Filament\Resources\CategoriasSoftware\Pages;

use App\Filament\Resources\CategoriasSoftware\CategoriaSoftwareResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCategoriasSoftware extends ListRecords
{
    protected static string $resource = CategoriaSoftwareResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nueva categoría'),
        ];
    }
}
