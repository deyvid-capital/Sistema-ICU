<?php

namespace App\Filament\Resources\RecepcionEquipos;

use App\Filament\Resources\RecepcionEquipos\Pages\CreateRecepcionEquipo;
use App\Filament\Resources\RecepcionEquipos\Pages\EditRecepcionEquipo;
use App\Filament\Resources\RecepcionEquipos\Pages\ListRecepcionEquipos;
use App\Filament\Resources\RecepcionEquipos\Pages\ViewRecepcionEquipo;
use App\Filament\Resources\RecepcionEquipos\RelationManagers\FirmantesRelationManager;
use App\Filament\Resources\RecepcionEquipos\RelationManagers\PdfsRelationManager;
use App\Filament\Resources\RecepcionEquipos\Schemas\RecepcionEquipoForm;
use App\Filament\Resources\RecepcionEquipos\Schemas\RecepcionEquipoInfolist;
use App\Filament\Resources\RecepcionEquipos\Tables\RecepcionEquiposTable;
use App\Models\RecepcionEquipo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class RecepcionEquipoResource extends Resource
{
    protected static ?string $model = RecepcionEquipo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static string|UnitEnum|null $navigationGroup = 'Formularios Entrega-Recepcion';

    protected static ?string $navigationLabel = 'Recepciones de equipo';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'recepción de equipo';

    protected static ?string $pluralModelLabel = 'recepciones de equipo';

    protected static ?string $recordTitleAttribute = 'numero';

    public static function form(Schema $schema): Schema
    {
        return RecepcionEquipoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RecepcionEquipoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecepcionEquiposTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PdfsRelationManager::class,
            FirmantesRelationManager::class,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['numero', 'observaciones'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecepcionEquipos::route('/'),
            'create' => CreateRecepcionEquipo::route('/create'),
            'view' => ViewRecepcionEquipo::route('/{record}'),
            'edit' => EditRecepcionEquipo::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canEdit(Model $record): bool
    {
        return parent::canEdit($record) && ! $record->trashed();
    }
}
