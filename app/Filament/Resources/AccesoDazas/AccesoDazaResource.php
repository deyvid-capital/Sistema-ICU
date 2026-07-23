<?php

namespace App\Filament\Resources\AccesoDazas;

use App\Filament\Resources\AccesoDazas\Pages\CreateAccesoDaza;
use App\Filament\Resources\AccesoDazas\Pages\EditAccesoDaza;
use App\Filament\Resources\AccesoDazas\Pages\ListAccesoDazas;
use App\Filament\Resources\AccesoDazas\Pages\ViewAccesoDaza;
use App\Filament\Resources\AccesoDazas\Schemas\AccesoDazaForm;
use App\Filament\Resources\AccesoDazas\Schemas\AccesoDazaInfolist;
use App\Filament\Resources\AccesoDazas\Tables\AccesoDazasTable;
use App\Models\AccesoDaza;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class AccesoDazaResource extends Resource
{
    protected static ?string $model = AccesoDaza::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Accesos';

    protected static ?string $navigationLabel = 'Accesos DAZA';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'acceso DAZA';

    protected static ?string $pluralModelLabel = 'accesos DAZA';

    protected static ?string $recordTitleAttribute = 'usuario_daza';

    public static function form(Schema $schema): Schema
    {
        return AccesoDazaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AccesoDazaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AccesoDazasTable::configure($table);
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
        return ['usuario_daza', 'nivel_acceso', 'ambiente', 'usuario.name'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccesoDazas::route('/'),
            'create' => CreateAccesoDaza::route('/create'),
            'view' => ViewAccesoDaza::route('/{record}'),
            'edit' => EditAccesoDaza::route('/{record}/edit'),
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
