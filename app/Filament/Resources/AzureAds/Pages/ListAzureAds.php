<?php

namespace App\Filament\Resources\AzureAds\Pages;

use App\Filament\Resources\AzureAds\AzureAdResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAzureAds extends ListRecords
{
    protected static string $resource = AzureAdResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nueva cuenta Azure AD'),
        ];
    }
}
