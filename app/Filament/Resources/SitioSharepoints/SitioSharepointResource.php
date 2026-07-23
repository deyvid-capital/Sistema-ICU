<?php

namespace App\Filament\Resources\SitioSharepoints;

use App\Filament\Resources\SitioSharepoints\Pages\CreateSitioSharepoint;
use App\Filament\Resources\SitioSharepoints\Pages\EditSitioSharepoint;
use App\Filament\Resources\SitioSharepoints\Pages\ListSitioSharepoints;
use App\Filament\Resources\SitioSharepoints\Pages\ViewSitioSharepoint;
use App\Filament\Resources\SitioSharepoints\RelationManagers\AccesosRelationManager;
use App\Filament\Resources\SitioSharepoints\Schemas\SitioSharepointForm;
use App\Filament\Resources\SitioSharepoints\Schemas\SitioSharepointInfolist;
use App\Filament\Resources\SitioSharepoints\Tables\SitioSharepointsTable;
use App\Models\SitioSharepoint;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SitioSharepointResource extends Resource
{
    protected static ?string $model = SitioSharepoint::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|UnitEnum|null $navigationGroup = 'Fondos y Recursos Compartidos';

    protected static ?string $navigationLabel = 'Sitios SharePoint';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'sitio SharePoint';

    protected static ?string $pluralModelLabel = 'sitios SharePoint';

    protected static ?string $recordTitleAttribute = 'nombre_sitio';

    public static function form(Schema $schema): Schema
    {
        return SitioSharepointForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SitioSharepointInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SitioSharepointsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AccesosRelationManager::class,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['nombre_sitio', 'url', 'descripcion'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSitioSharepoints::route('/'),
            'create' => CreateSitioSharepoint::route('/create'),
            'view' => ViewSitioSharepoint::route('/{record}'),
            'edit' => EditSitioSharepoint::route('/{record}/edit'),
        ];
    }
}
