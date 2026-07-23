<?php

namespace App\Filament\Resources\Licencias;

use App\Filament\Resources\Licencias\Pages\CreateLicencia;
use App\Filament\Resources\Licencias\Pages\EditLicencia;
use App\Filament\Resources\Licencias\Pages\ListLicencias;
use App\Filament\Resources\Licencias\Pages\ViewLicencia;
use App\Filament\Resources\Licencias\RelationManagers\LicenciasUsuariosRelationManager;
use App\Filament\Resources\Licencias\Schemas\LicenciaForm;
use App\Filament\Resources\Licencias\Schemas\LicenciaInfolist;
use App\Filament\Resources\Licencias\Tables\LicenciasTable;
use App\Models\Licencia;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class LicenciaResource extends Resource
{
    protected static ?string $model = Licencia::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'Software y Licencias';

    protected static ?string $navigationLabel = 'Licencias';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'licencia';

    protected static ?string $pluralModelLabel = 'licencias';

    protected static ?string $recordTitleAttribute = 'nombre_licencia';

    public static function form(Schema $schema): Schema
    {
        return LicenciaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LicenciaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LicenciasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            LicenciasUsuariosRelationManager::class,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['nombre_licencia', 'categoria', 'descripcion'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLicencias::route('/'),
            'create' => CreateLicencia::route('/create'),
            'view' => ViewLicencia::route('/{record}'),
            'edit' => EditLicencia::route('/{record}/edit'),
        ];
    }
}
