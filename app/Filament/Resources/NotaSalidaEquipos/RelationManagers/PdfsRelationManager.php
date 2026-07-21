<?php

namespace App\Filament\Resources\NotaSalidaEquipos\RelationManagers;

use App\Filament\Resources\PdfEquipos\PdfEquipoResource;
use App\Filament\Resources\Users\RelationManagers\Concerns\HasOwnerRecordCountBadge;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class PdfsRelationManager extends RelationManager
{
    use HasOwnerRecordCountBadge;

    protected static string $relationship = 'pdfs';

    protected static ?string $title = 'PDFs';

    public function form(Schema $schema): Schema
    {
        return PdfEquipoResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return PdfEquipoResource::table($table)
            ->headerActions([
                CreateAction::make()
                    ->label('Agregar PDF')
                    ->successNotificationTitle('PDF registrado correctamente'),
            ]);
    }
}
