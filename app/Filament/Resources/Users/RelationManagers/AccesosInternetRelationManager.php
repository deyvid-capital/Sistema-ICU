<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\AccesoInternets\AccesoInternetResource;
use App\Filament\Resources\Users\RelationManagers\Concerns\HasOwnerRecordCountBadge;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class AccesosInternetRelationManager extends RelationManager
{
    use HasOwnerRecordCountBadge;

    protected static string $relationship = 'accesosInternet';

    protected static ?string $title = 'Accesos a Internet';

    public function form(Schema $schema): Schema
    {
        return AccesoInternetResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return AccesoInternetResource::table($table)
            ->headerActions([
                CreateAction::make()
                    ->label('Asignar acceso')
                    ->successNotificationTitle('Acceso registrado correctamente'),
            ]);
    }
}
