<?php

namespace App\Filament\Resources\AccesoVirtuals;

use App\Filament\Clusters\Accesos\AccesosCluster;
use App\Filament\Resources\AccesoVirtuals\Pages\CreateAccesoVirtual;
use App\Filament\Resources\AccesoVirtuals\Pages\EditAccesoVirtual;
use App\Filament\Resources\AccesoVirtuals\Pages\ListAccesoVirtuals;
use App\Filament\Resources\AccesoVirtuals\Pages\ViewAccesoVirtual;
use App\Filament\Resources\AccesoVirtuals\Schemas\AccesoVirtualForm;
use App\Filament\Resources\AccesoVirtuals\Schemas\AccesoVirtualInfolist;
use App\Filament\Resources\AccesoVirtuals\Tables\AccesoVirtualsTable;
use App\Models\AccesoVirtual;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccesoVirtualResource extends Resource
{
    protected static ?string $model = AccesoVirtual::class;

    protected static ?string $cluster = AccesosCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServer;

    protected static ?string $navigationLabel = 'Accesos virtuales';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'acceso virtual';

    protected static ?string $pluralModelLabel = 'accesos virtuales';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return AccesoVirtualForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AccesoVirtualInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AccesoVirtualsTable::configure($table);
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
        return ['usuario.name', 'virtual.maquina_virtual', 'recursoVpn.nombre_vpn'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccesoVirtuals::route('/'),
            'create' => CreateAccesoVirtual::route('/create'),
            'view' => ViewAccesoVirtual::route('/{record}'),
            'edit' => EditAccesoVirtual::route('/{record}/edit'),
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
