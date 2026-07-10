<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\AccesoDazas\AccesoDazaResource;
use App\Filament\Resources\Users\RelationManagers\Concerns\HasOwnerRecordCountBadge;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class AccesosDazaRelationManager extends RelationManager
{
    use HasOwnerRecordCountBadge;

    protected static string $relationship = 'accesosDaza';

    protected static ?string $title = 'Accesos DAZA';

    public function form(Schema $schema): Schema
    {
        return AccesoDazaResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return AccesoDazaResource::table($table)
            ->headerActions([
                CreateAction::make()
                    ->label('Asignar acceso')
                    ->successNotificationTitle('Acceso registrado correctamente'),
            ]);
    }
}
