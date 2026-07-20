<?php

namespace App\Filament\Resources\Equipos\RelationManagers;

use App\Filament\Resources\NotaSalidaEquipos\NotaSalidaEquipoResource;
use App\Filament\Resources\Users\RelationManagers\Concerns\HasOwnerRecordCountBadge;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class NotasSalidaRelationManager extends RelationManager
{
    use HasOwnerRecordCountBadge;

    protected static string $relationship = 'notasSalida';

    protected static ?string $title = 'Notas de salida';

    public function form(Schema $schema): Schema
    {
        return NotaSalidaEquipoResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return NotaSalidaEquipoResource::table($table)
            ->headerActions([
                CreateAction::make()
                    ->label('Registrar nota de salida')
                    ->successNotificationTitle('Nota de salida registrada correctamente'),
            ]);
    }
}
