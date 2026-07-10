<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\AccesoSharepoints\AccesoSharepointResource;
use App\Filament\Resources\Users\RelationManagers\Concerns\HasOwnerRecordCountBadge;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class AccesosSharepointRelationManager extends RelationManager
{
    use HasOwnerRecordCountBadge;

    protected static string $relationship = 'accesosSharepoint';

    protected static ?string $title = 'Accesos SharePoint';

    public function form(Schema $schema): Schema
    {
        return AccesoSharepointResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return AccesoSharepointResource::table($table)
            ->headerActions([
                CreateAction::make()
                    ->label('Asignar acceso')
                    ->successNotificationTitle('Acceso registrado correctamente'),
            ]);
    }
}
