<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Actions\AsignarSoftwarePorDefectoAction;
use App\Filament\Resources\SoftwareUsuarios\SoftwareUsuarioResource;
use App\Filament\Resources\Users\RelationManagers\Concerns\HasOwnerRecordCountBadge;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class SoftwareUsuariosRelationManager extends RelationManager
{
    use HasOwnerRecordCountBadge;

    protected static string $relationship = 'softwareUsuarios';

    protected static ?string $title = 'Software';

    public function form(Schema $schema): Schema
    {
        return SoftwareUsuarioResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return SoftwareUsuarioResource::table($table)
            ->headerActions([
                CreateAction::make()
                    ->label('Asignar software')
                    ->successNotificationTitle('Asignación registrada correctamente'),
                AsignarSoftwarePorDefectoAction::make($this),
            ])
            ->poll('5s');
    }
}
