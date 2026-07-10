<?php

namespace App\Filament\Resources\AccesoSistemas;

use App\Filament\Resources\AccesoSistemas\Pages\CreateAccesoSistema;
use App\Filament\Resources\AccesoSistemas\Pages\EditAccesoSistema;
use App\Filament\Resources\AccesoSistemas\Pages\ListAccesoSistemas;
use App\Filament\Resources\AccesoSistemas\Pages\ViewAccesoSistema;
use App\Filament\Resources\AccesoSistemas\Schemas\AccesoSistemaForm;
use App\Filament\Resources\AccesoSistemas\Schemas\AccesoSistemaInfolist;
use App\Filament\Resources\AccesoSistemas\Tables\AccesoSistemasTable;
use App\Models\AccesoSistema;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class AccesoSistemaResource extends Resource
{
    protected static ?string $model = AccesoSistema::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static string|UnitEnum|null $navigationGroup = 'Accesos';

    protected static ?string $navigationLabel = 'Accesos a sistemas';

    protected static ?int $navigationSort = 12;

    protected static ?string $modelLabel = 'acceso a sistema';

    protected static ?string $pluralModelLabel = 'accesos a sistemas';

    protected static ?string $recordTitleAttribute = 'usuario_sistema';

    public static function form(Schema $schema): Schema
    {
        return AccesoSistemaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AccesoSistemaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AccesoSistemasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccesoSistemas::route('/'),
            'create' => CreateAccesoSistema::route('/create'),
            'view' => ViewAccesoSistema::route('/{record}'),
            'edit' => EditAccesoSistema::route('/{record}/edit'),
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
