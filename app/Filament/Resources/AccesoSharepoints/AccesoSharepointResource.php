<?php

namespace App\Filament\Resources\AccesoSharepoints;

use App\Filament\Clusters\Accesos\AccesosCluster;
use App\Filament\Resources\AccesoSharepoints\Pages\CreateAccesoSharepoint;
use App\Filament\Resources\AccesoSharepoints\Pages\EditAccesoSharepoint;
use App\Filament\Resources\AccesoSharepoints\Pages\ListAccesoSharepoints;
use App\Filament\Resources\AccesoSharepoints\Pages\ViewAccesoSharepoint;
use App\Filament\Resources\AccesoSharepoints\Schemas\AccesoSharepointForm;
use App\Filament\Resources\AccesoSharepoints\Schemas\AccesoSharepointInfolist;
use App\Filament\Resources\AccesoSharepoints\Tables\AccesoSharepointsTable;
use App\Models\AccesoSharepoint;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccesoSharepointResource extends Resource
{
    protected static ?string $model = AccesoSharepoint::class;

    protected static ?string $cluster = AccesosCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShare;

    protected static ?string $navigationLabel = 'Accesos SharePoint';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'acceso SharePoint';

    protected static ?string $pluralModelLabel = 'accesos SharePoint';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return AccesoSharepointForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AccesoSharepointInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AccesoSharepointsTable::configure($table);
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
        return ['estado', 'usuario.name', 'sitio.nombre_sitio'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccesoSharepoints::route('/'),
            'create' => CreateAccesoSharepoint::route('/create'),
            'view' => ViewAccesoSharepoint::route('/{record}'),
            'edit' => EditAccesoSharepoint::route('/{record}/edit'),
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
