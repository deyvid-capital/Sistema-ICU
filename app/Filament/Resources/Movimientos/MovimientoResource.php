<?php

namespace App\Filament\Resources\Movimientos;

use App\Filament\Clusters\Usuarios\UsuariosCluster;
use App\Filament\Resources\Movimientos\Pages\CreateMovimiento;
use App\Filament\Resources\Movimientos\Pages\EditMovimiento;
use App\Filament\Resources\Movimientos\Pages\ListMovimientos;
use App\Filament\Resources\Movimientos\Pages\ViewMovimiento;
use App\Filament\Resources\Movimientos\Schemas\MovimientoForm;
use App\Filament\Resources\Movimientos\Schemas\MovimientoInfolist;
use App\Filament\Resources\Movimientos\Tables\MovimientosTable;
use App\Models\Movimiento;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MovimientoResource extends Resource
{
    protected static ?string $model = Movimiento::class;

    protected static ?string $cluster = UsuariosCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?string $recordTitleAttribute = 'tipo';

    protected static ?string $navigationLabel = 'Movimientos';

    protected static ?string $modelLabel = 'movimiento';

    protected static ?string $pluralModelLabel = 'movimientos';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return MovimientoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MovimientoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MovimientosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['tipo', 'usuario.name', 'cargo.cargo'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMovimientos::route('/'),
            'create' => CreateMovimiento::route('/create'),
            'view' => ViewMovimiento::route('/{record}'),
            'edit' => EditMovimiento::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
