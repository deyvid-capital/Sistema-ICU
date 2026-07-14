<?php

namespace App\Filament\Resources\AzureAds\Pages;

use App\Filament\Resources\AzureAds\AzureAdResource;
use App\Models\AzureAd;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAzureAd extends ViewRecord
{
    protected static string $resource = AzureAdResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Editar cuenta')
                ->visible(fn (AzureAd $record): bool => ! $record->trashed()),
        ];
    }
}
