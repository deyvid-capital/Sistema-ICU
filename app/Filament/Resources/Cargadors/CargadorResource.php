<?php

namespace App\Filament\Resources\Cargadors;

use App\Filament\Clusters\Inventario\InventarioCluster;
use App\Filament\Resources\Cargadors\Pages\CreateCargador;
use App\Filament\Resources\Cargadors\Pages\EditCargador;
use App\Filament\Resources\Cargadors\Pages\ListCargadors;
use App\Filament\Resources\Cargadors\Pages\ViewCargador;
use App\Filament\Resources\Cargadors\RelationManagers\AsignacionesRelationManager;
use App\Filament\Resources\Cargadors\Schemas\CargadorForm;
use App\Filament\Resources\Cargadors\Schemas\CargadorInfolist;
use App\Filament\Resources\Cargadors\Tables\CargadorsTable;
use App\Models\Cargador;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CargadorResource extends Resource
{
    protected static ?string $model = Cargador::class;

    protected static ?string $cluster = InventarioCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static ?string $navigationLabel = 'Cargadores';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'cargador';

    protected static ?string $pluralModelLabel = 'cargadores';

    protected static ?string $recordTitleAttribute = 'serial';

    public static function form(Schema $schema): Schema
    {
        return CargadorForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CargadorInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CargadorsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AsignacionesRelationManager::class,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['serial', 'marca', 'modelo'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCargadors::route('/'),
            'create' => CreateCargador::route('/create'),
            'view' => ViewCargador::route('/{record}'),
            'edit' => EditCargador::route('/{record}/edit'),
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
