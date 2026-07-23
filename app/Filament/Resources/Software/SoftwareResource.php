<?php

namespace App\Filament\Resources\Software;

use App\Filament\Resources\Software\Pages\CreateSoftware;
use App\Filament\Resources\Software\Pages\EditSoftware;
use App\Filament\Resources\Software\Pages\ListSoftware;
use App\Filament\Resources\Software\Pages\ViewSoftware;
use App\Filament\Resources\Software\RelationManagers\AreasRelationManager;
use App\Filament\Resources\Software\RelationManagers\ExcepcionesRelationManager;
use App\Filament\Resources\Software\RelationManagers\UsuariosRelationManager;
use App\Filament\Resources\Software\Schemas\SoftwareForm;
use App\Filament\Resources\Software\Schemas\SoftwareInfolist;
use App\Filament\Resources\Software\Tables\SoftwareTable;
use App\Models\Software;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SoftwareResource extends Resource
{
    protected static ?string $model = Software::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static string|UnitEnum|null $navigationGroup = 'Software y Licencias';

    protected static ?string $navigationLabel = 'Software';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'software';

    protected static ?string $pluralModelLabel = 'software';

    protected static ?string $recordTitleAttribute = 'nombre_software';

    public static function form(Schema $schema): Schema
    {
        return SoftwareForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SoftwareInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SoftwareTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AreasRelationManager::class,
            UsuariosRelationManager::class,
            ExcepcionesRelationManager::class,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['nombre_software', 'categoria', 'descripcion'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSoftware::route('/'),
            'create' => CreateSoftware::route('/create'),
            'view' => ViewSoftware::route('/{record}'),
            'edit' => EditSoftware::route('/{record}/edit'),
        ];
    }
}
