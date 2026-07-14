<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\SoftwareExcs\SoftwareExcResource;
use App\Filament\Resources\Users\RelationManagers\Concerns\HasOwnerRecordCountBadge;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class SoftwareExcepcionesRelationManager extends RelationManager
{
    use HasOwnerRecordCountBadge;

    protected static string $relationship = 'softwareExcepciones';

    protected static ?string $title = 'Excepciones de software';

    public function form(Schema $schema): Schema
    {
        return SoftwareExcResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return SoftwareExcResource::table($table)
            ->headerActions([
                CreateAction::make()
                    ->label('Nueva excepción')
                    ->successNotificationTitle('Excepción registrada correctamente'),
            ]);
    }
}
