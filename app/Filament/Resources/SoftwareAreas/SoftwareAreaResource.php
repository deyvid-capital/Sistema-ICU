<?php

namespace App\Filament\Resources\SoftwareAreas;

use App\Filament\Resources\SoftwareAreas\Pages\CreateSoftwareArea;
use App\Filament\Resources\SoftwareAreas\Pages\EditSoftwareArea;
use App\Filament\Resources\SoftwareAreas\Pages\ListSoftwareAreas;
use App\Filament\Resources\SoftwareAreas\Pages\ViewSoftwareArea;
use App\Filament\Resources\SoftwareAreas\Schemas\SoftwareAreaForm;
use App\Filament\Resources\SoftwareAreas\Schemas\SoftwareAreaInfolist;
use App\Filament\Resources\SoftwareAreas\Tables\SoftwareAreasTable;
use App\Models\SoftwareArea;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SoftwareAreaResource extends Resource
{
    protected static ?string $model = SoftwareArea::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'Software y Licencias';

    protected static ?string $navigationLabel = 'Software por area';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'software por area';

    protected static ?string $pluralModelLabel = 'software por area';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return SoftwareAreaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SoftwareAreaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SoftwareAreasTable::configure($table);
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
            'index' => ListSoftwareAreas::route('/'),
            'create' => CreateSoftwareArea::route('/create'),
            'view' => ViewSoftwareArea::route('/{record}'),
            'edit' => EditSoftwareArea::route('/{record}/edit'),
        ];
    }
}
