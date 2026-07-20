<?php

namespace App\Filament\Resources\RecepcionEquipos\RelationManagers;

use App\Filament\Resources\Firmantes\FirmanteResource;
use App\Filament\Resources\Users\RelationManagers\Concerns\HasOwnerRecordCountBadge;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class FirmantesRelationManager extends RelationManager
{
    use HasOwnerRecordCountBadge;

    protected static string $relationship = 'firmantes';

    protected static ?string $title = 'Firmantes';

    public function form(Schema $schema): Schema
    {
        return FirmanteResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return FirmanteResource::table($table)
            ->headerActions([
                CreateAction::make()
                    ->label('Agregar firmante')
                    ->successNotificationTitle('Firmante registrado correctamente'),
            ]);
    }
}
