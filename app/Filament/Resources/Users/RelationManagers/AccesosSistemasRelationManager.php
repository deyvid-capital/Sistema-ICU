<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\AccesoSistemas\AccesoSistemaResource;
use App\Filament\Resources\Users\RelationManagers\Concerns\HasOwnerRecordCountBadge;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class AccesosSistemasRelationManager extends RelationManager
{
    use HasOwnerRecordCountBadge;

    protected static string $relationship = 'accesosSistemas';

    protected static ?string $title = 'Accesos a sistemas';

    public function form(Schema $schema): Schema
    {
        return AccesoSistemaResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return AccesoSistemaResource::table($table)
            ->headerActions([
                CreateAction::make()
                    ->label('Asignar acceso')
                    ->successNotificationTitle('Acceso registrado correctamente'),
            ]);
    }
}
