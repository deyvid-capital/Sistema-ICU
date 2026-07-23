<?php

namespace App\Filament\Resources\EntregaEquipos;

use App\Filament\Resources\EntregaEquipos\Pages\CreateEntregaEquipo;
use App\Filament\Resources\EntregaEquipos\Pages\EditEntregaEquipo;
use App\Filament\Resources\EntregaEquipos\Pages\ListEntregaEquipos;
use App\Filament\Resources\EntregaEquipos\Pages\ViewEntregaEquipo;
use App\Filament\Resources\EntregaEquipos\RelationManagers\FirmantesRelationManager;
use App\Filament\Resources\EntregaEquipos\RelationManagers\PdfsRelationManager;
use App\Filament\Resources\EntregaEquipos\Schemas\EntregaEquipoForm;
use App\Filament\Resources\EntregaEquipos\Schemas\EntregaEquipoInfolist;
use App\Filament\Resources\EntregaEquipos\Tables\EntregaEquiposTable;
use App\Models\EntregaEquipo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class EntregaEquipoResource extends Resource
{
    protected static ?string $model = EntregaEquipo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static string|UnitEnum|null $navigationGroup = 'Formularios Entrega-Recepcion';

    protected static ?string $navigationLabel = 'Entregas de equipo';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'entrega de equipo';

    protected static ?string $pluralModelLabel = 'entregas de equipo';

    protected static ?string $recordTitleAttribute = 'numero';

    public static function form(Schema $schema): Schema
    {
        return EntregaEquipoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EntregaEquipoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EntregaEquiposTable::configure($table);
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
        return ['numero', 'observaciones', 'nota'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEntregaEquipos::route('/'),
            'create' => CreateEntregaEquipo::route('/create'),
            'view' => ViewEntregaEquipo::route('/{record}'),
            'edit' => EditEntregaEquipo::route('/{record}/edit'),
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
