<?php

namespace App\Filament\Resources\MantenimientoEquipos;

use App\Filament\Resources\MantenimientoEquipos\Pages\CreateMantenimientoEquipo;
use App\Filament\Resources\MantenimientoEquipos\Pages\EditMantenimientoEquipo;
use App\Filament\Resources\MantenimientoEquipos\Pages\ListMantenimientoEquipos;
use App\Filament\Resources\MantenimientoEquipos\Pages\ViewMantenimientoEquipo;
use App\Filament\Resources\MantenimientoEquipos\Schemas\MantenimientoEquipoForm;
use App\Filament\Resources\MantenimientoEquipos\Schemas\MantenimientoEquipoInfolist;
use App\Filament\Resources\MantenimientoEquipos\Tables\MantenimientoEquiposTable;
use App\Models\MantenimientoEquipo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class MantenimientoEquipoResource extends Resource
{
    protected static ?string $model = MantenimientoEquipo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|UnitEnum|null $navigationGroup = 'Laptop y Computadoras';

    protected static ?string $navigationLabel = 'Mantenimientos de equipos';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'mantenimiento de equipo';

    protected static ?string $pluralModelLabel = 'mantenimientos de equipos';

    protected static ?string $recordTitleAttribute = null;

    public static function form(Schema $schema): Schema
    {
        return MantenimientoEquipoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MantenimientoEquipoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MantenimientoEquiposTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMantenimientoEquipos::route('/'),
            'create' => CreateMantenimientoEquipo::route('/create'),
            'view' => ViewMantenimientoEquipo::route('/{record}'),
            'edit' => EditMantenimientoEquipo::route('/{record}/edit'),
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
