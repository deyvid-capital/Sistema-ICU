<?php

namespace App\Filament\Resources\Sistemas\Pages;

use App\Filament\Resources\Sistemas\SistemaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSistemas extends ListRecords
{
    protected static string $resource = SistemaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo sistema'),
        ];
    }
}
