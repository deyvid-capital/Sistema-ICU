<?php

namespace App\Filament\Resources\AsignacionEquipos;

use App\Filament\Resources\AsignacionEquipos\Pages\CreateAsignacionEquipo;
use App\Filament\Resources\AsignacionEquipos\Pages\EditAsignacionEquipo;
use App\Filament\Resources\AsignacionEquipos\Pages\ListAsignacionEquipos;
use App\Filament\Resources\AsignacionEquipos\Pages\ViewAsignacionEquipo;
use App\Filament\Resources\AsignacionEquipos\Schemas\AsignacionEquipoForm;
use App\Filament\Resources\AsignacionEquipos\Schemas\AsignacionEquipoInfolist;
use App\Filament\Resources\AsignacionEquipos\Tables\AsignacionEquiposTable;
use App\Models\AsignacionEquipo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class AsignacionEquipoResource extends Resource
{
    protected static ?string $model = AsignacionEquipo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static string|UnitEnum|null $navigationGroup = 'Laptop y Computadoras';

    protected static ?string $navigationLabel = 'Asig. Equipos';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'asignación de equipo';

    protected static ?string $pluralModelLabel = 'asignaciones de equipos';

    protected static ?string $recordTitleAttribute = null;

    public static function form(Schema $schema): Schema
    {
        return AsignacionEquipoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AsignacionEquipoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AsignacionEquiposTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAsignacionEquipos::route('/'),
            'create' => CreateAsignacionEquipo::route('/create'),
            'view' => ViewAsignacionEquipo::route('/{record}'),
            'edit' => EditAsignacionEquipo::route('/{record}/edit'),
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
