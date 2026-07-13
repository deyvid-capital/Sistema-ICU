<?php

namespace App\Filament\Resources\Areas\RelationManagers;

use App\Filament\Resources\Cargos\CargoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CargosRelationManager extends RelationManager
{
    protected static string $relationship = 'cargos';

    protected static ?string $title = 'Cargos';

    public function form(Schema $schema): Schema
    {
        return CargoResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return CargoResource::table($table)
            ->headerActions([
                CreateAction::make()
                    ->label('Nuevo cargo')
                    ->successNotificationTitle('Cargo creado correctamente'),
            ]);
    }
}
