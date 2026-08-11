<?php

namespace App\Filament\Resources\AsignacionCargadors;

use App\Filament\Clusters\Equipos\EquiposCluster;
use App\Filament\Resources\AsignacionCargadors\Pages\CreateAsignacionCargador;
use App\Filament\Resources\AsignacionCargadors\Pages\EditAsignacionCargador;
use App\Filament\Resources\AsignacionCargadors\Pages\ListAsignacionCargadors;
use App\Filament\Resources\AsignacionCargadors\Pages\ViewAsignacionCargador;
use App\Filament\Resources\AsignacionCargadors\Schemas\AsignacionCargadorForm;
use App\Filament\Resources\AsignacionCargadors\Schemas\AsignacionCargadorInfolist;
use App\Filament\Resources\AsignacionCargadors\Tables\AsignacionCargadorsTable;
use App\Models\AsignacionCargador;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AsignacionCargadorResource extends Resource
{
    protected static ?string $model = AsignacionCargador::class;

    protected static ?string $cluster = EquiposCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static ?string $navigationLabel = 'Asig. Cargadores';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'asignación de cargador';

    protected static ?string $pluralModelLabel = 'asignaciones de cargadores';

    protected static ?string $recordTitleAttribute = 'serial_cargador';

    public static function form(Schema $schema): Schema
    {
        return AsignacionCargadorForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AsignacionCargadorInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AsignacionCargadorsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['serial_cargador', 'service_tag', 'observaciones'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAsignacionCargadors::route('/'),
            'create' => CreateAsignacionCargador::route('/create'),
            'view' => ViewAsignacionCargador::route('/{record}'),
            'edit' => EditAsignacionCargador::route('/{record}/edit'),
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
