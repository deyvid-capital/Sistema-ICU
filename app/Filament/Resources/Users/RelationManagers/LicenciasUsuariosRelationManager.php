<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\LicenciasUsuarios\LicenciaUsuarioResource;
use App\Filament\Resources\Users\RelationManagers\Concerns\HasOwnerRecordCountBadge;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class LicenciasUsuariosRelationManager extends RelationManager
{
    use HasOwnerRecordCountBadge;

    protected static string $relationship = 'licenciasUsuarios';

    protected static ?string $title = 'Licencias';

    public function form(Schema $schema): Schema
    {
        return LicenciaUsuarioResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return LicenciaUsuarioResource::table($table)
            ->headerActions([
                CreateAction::make()
                    ->label('Asignar licencia')
                    ->successNotificationTitle('Asignación registrada correctamente'),
            ])
            ->poll('5s');
    }
}
