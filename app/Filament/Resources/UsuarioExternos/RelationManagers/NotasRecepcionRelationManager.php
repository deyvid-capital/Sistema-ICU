<?php

namespace App\Filament\Resources\UsuarioExternos\RelationManagers;

use App\Filament\Resources\NotaRecepcionEquipos\NotaRecepcionEquipoResource;
use App\Filament\Resources\Users\RelationManagers\Concerns\HasOwnerRecordCountBadge;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class NotasRecepcionRelationManager extends RelationManager
{
    use HasOwnerRecordCountBadge;

    protected static string $relationship = 'notasRecepcion';

    protected static ?string $title = 'Notas de recepción';

    public function form(Schema $schema): Schema
    {
        return NotaRecepcionEquipoResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return NotaRecepcionEquipoResource::table($table)
            ->headerActions([
                CreateAction::make()
                    ->label('Registrar nota de recepción')
                    ->successNotificationTitle('Nota de recepción registrada correctamente'),
            ]);
    }
}
