<?php

namespace App\Filament\Resources\AccesoInternets;

use App\Filament\Resources\AccesoInternets\Pages\CreateAccesoInternet;
use App\Filament\Resources\AccesoInternets\Pages\EditAccesoInternet;
use App\Filament\Resources\AccesoInternets\Pages\ListAccesoInternets;
use App\Filament\Resources\AccesoInternets\Pages\ViewAccesoInternet;
use App\Filament\Resources\AccesoInternets\RelationManagers\AccesosVirtualRelationManager;
use App\Filament\Resources\AccesoInternets\Schemas\AccesoInternetForm;
use App\Filament\Resources\AccesoInternets\Schemas\AccesoInternetInfolist;
use App\Filament\Resources\AccesoInternets\Tables\AccesoInternetsTable;
use App\Models\AccesoInternet;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class AccesoInternetResource extends Resource
{
    protected static ?string $model = AccesoInternet::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWifi;

    protected static string|UnitEnum|null $navigationGroup = 'Accesos';

    protected static ?string $navigationLabel = 'Accesos Wifi';

    protected static ?int $navigationSort = 13;

    protected static ?string $modelLabel = 'acceso a Wifi';

    protected static ?string $pluralModelLabel = 'accesos a Wifi';

    protected static ?string $recordTitleAttribute = 'usuario_red';

    public static function form(Schema $schema): Schema
    {
        return AccesoInternetForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AccesoInternetInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AccesoInternetsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AccesosVirtualRelationManager::class,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['usuario_red', 'nivel_acceso', 'fortitoken', 'usuario.name'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccesoInternets::route('/'),
            'create' => CreateAccesoInternet::route('/create'),
            'view' => ViewAccesoInternet::route('/{record}'),
            'edit' => EditAccesoInternet::route('/{record}/edit'),
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
