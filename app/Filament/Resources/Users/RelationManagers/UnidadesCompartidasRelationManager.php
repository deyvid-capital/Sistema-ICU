<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\AccUdCompartidas\AccUdCompartidaResource;
use App\Filament\Resources\Users\RelationManagers\Concerns\HasOwnerRecordCountBadge;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class UnidadesCompartidasRelationManager extends RelationManager
{
    use HasOwnerRecordCountBadge;

    protected static string $relationship = 'unidadesCompartidas';

    protected static ?string $title = 'Unidades compartidas';

    public function form(Schema $schema): Schema
    {
        return AccUdCompartidaResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return AccUdCompartidaResource::table($table)
            ->headerActions([
                CreateAction::make()
                    ->label('Asignar unidad')
                    ->successNotificationTitle('Unidad registrada correctamente'),
            ]);
    }
}
