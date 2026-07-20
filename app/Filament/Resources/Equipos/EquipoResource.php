<?php

namespace App\Filament\Resources\Equipos;

use App\Filament\Resources\Equipos\Pages\CreateEquipo;
use App\Filament\Resources\Equipos\Pages\EditEquipo;
use App\Filament\Resources\Equipos\Pages\ListEquipos;
use App\Filament\Resources\Equipos\Pages\ViewEquipo;
use App\Filament\Resources\Equipos\RelationManagers\AsignacionesCargadorRelationManager;
use App\Filament\Resources\Equipos\RelationManagers\AsignacionesUsuarioRelationManager;
use App\Filament\Resources\Equipos\RelationManagers\RecibosEquipoRelationManager;
use App\Filament\Resources\Equipos\Schemas\EquipoForm;
use App\Filament\Resources\Equipos\Schemas\EquipoInfolist;
use App\Filament\Resources\Equipos\Tables\EquiposTable;
use App\Models\Equipo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class EquipoResource extends Resource
{
    protected static ?string $model = Equipo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static string|UnitEnum|null $navigationGroup = 'Inventario Equipos y Componentes';

    protected static ?string $navigationLabel = 'Equipos';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'equipo';

    protected static ?string $pluralModelLabel = 'equipos';

    protected static ?string $recordTitleAttribute = 'service_tag';

    public static function form(Schema $schema): Schema
    {
        return EquipoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EquipoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EquiposTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AsignacionesUsuarioRelationManager::class,
            AsignacionesCargadorRelationManager::class,
            RecibosEquipoRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEquipos::route('/'),
            'create' => CreateEquipo::route('/create'),
            'view' => ViewEquipo::route('/{record}'),
            'edit' => EditEquipo::route('/{record}/edit'),
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
