<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\AzureAds\AzureAdResource;
use App\Filament\Resources\Users\RelationManagers\Concerns\HasOwnerRecordCountBadge;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class AzureAdRelationManager extends RelationManager
{
    use HasOwnerRecordCountBadge;

    protected static string $relationship = 'azureAd';

    protected static ?string $title = 'Azure AD';

    public function form(Schema $schema): Schema
    {
        return AzureAdResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return AzureAdResource::table($table)
            ->headerActions([
                CreateAction::make()
                    ->label('Registrar cuenta')
                    ->successNotificationTitle('Cuenta Azure AD registrada correctamente'),
            ]);
    }
}
