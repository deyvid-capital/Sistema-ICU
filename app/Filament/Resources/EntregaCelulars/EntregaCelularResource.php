<?php

namespace App\Filament\Resources\EntregaCelulars;

use App\Filament\Resources\EntregaCelulars\Pages\CreateEntregaCelular;
use App\Filament\Resources\EntregaCelulars\Pages\EditEntregaCelular;
use App\Filament\Resources\EntregaCelulars\Pages\ListEntregaCelulars;
use App\Filament\Resources\EntregaCelulars\Pages\ViewEntregaCelular;
use App\Filament\Resources\EntregaCelulars\RelationManagers\FirmantesRelationManager;
use App\Filament\Resources\EntregaCelulars\RelationManagers\PdfsRelationManager;
use App\Filament\Resources\EntregaCelulars\Schemas\EntregaCelularForm;
use App\Filament\Resources\EntregaCelulars\Schemas\EntregaCelularInfolist;
use App\Filament\Resources\EntregaCelulars\Tables\EntregaCelularsTable;
use App\Models\EntregaCelular;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class EntregaCelularResource extends Resource
{
    protected static ?string $model = EntregaCelular::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static string|UnitEnum|null $navigationGroup = 'Formularios Entrega-Recepcion';

    protected static ?string $navigationLabel = 'Entregas de celular';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'entrega de celular';

    protected static ?string $pluralModelLabel = 'entregas de celular';

    protected static ?string $recordTitleAttribute = 'numero';

    public static function form(Schema $schema): Schema
    {
        return EntregaCelularForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EntregaCelularInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EntregaCelularsTable::configure($table);
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
            'index' => ListEntregaCelulars::route('/'),
            'create' => CreateEntregaCelular::route('/create'),
            'view' => ViewEntregaCelular::route('/{record}'),
            'edit' => EditEntregaCelular::route('/{record}/edit'),
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
