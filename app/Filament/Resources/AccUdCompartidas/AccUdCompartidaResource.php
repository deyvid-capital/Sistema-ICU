<?php

namespace App\Filament\Resources\AccUdCompartidas;

use App\Filament\Clusters\Accesos\AccesosCluster;
use App\Filament\Resources\AccUdCompartidas\Pages\CreateAccUdCompartida;
use App\Filament\Resources\AccUdCompartidas\Pages\EditAccUdCompartida;
use App\Filament\Resources\AccUdCompartidas\Pages\ListAccUdCompartidas;
use App\Filament\Resources\AccUdCompartidas\Pages\ViewAccUdCompartida;
use App\Filament\Resources\AccUdCompartidas\Schemas\AccUdCompartidaForm;
use App\Filament\Resources\AccUdCompartidas\Schemas\AccUdCompartidaInfolist;
use App\Filament\Resources\AccUdCompartidas\Tables\AccUdCompartidasTable;
use App\Models\AccUdCompartida;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccUdCompartidaResource extends Resource
{
    protected static ?string $model = AccUdCompartida::class;

    protected static ?string $cluster = AccesosCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static ?string $navigationLabel = 'Unidades compartidas';

    protected static ?int $navigationSort = 7;

    protected static ?string $modelLabel = 'unidad compartida';

    protected static ?string $pluralModelLabel = 'unidades compartidas';

    protected static ?string $recordTitleAttribute = 'unidad_compartida';

    public static function form(Schema $schema): Schema
    {
        return AccUdCompartidaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AccUdCompartidaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AccUdCompartidasTable::configure($table);
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
        return ['unidad_compartida', 'estado', 'usuario.name'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccUdCompartidas::route('/'),
            'create' => CreateAccUdCompartida::route('/create'),
            'view' => ViewAccUdCompartida::route('/{record}'),
            'edit' => EditAccUdCompartida::route('/{record}/edit'),
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
