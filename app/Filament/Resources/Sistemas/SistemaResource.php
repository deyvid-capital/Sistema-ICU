<?php

namespace App\Filament\Resources\Sistemas;

use App\Filament\Resources\Sistemas\Pages\CreateSistema;
use App\Filament\Resources\Sistemas\Pages\EditSistema;
use App\Filament\Resources\Sistemas\Pages\ListSistemas;
use App\Filament\Resources\Sistemas\Pages\ViewSistema;
use App\Filament\Resources\Sistemas\RelationManagers\AccesosRelationManager;
use App\Filament\Resources\Sistemas\Schemas\SistemaForm;
use App\Filament\Resources\Sistemas\Schemas\SistemaInfolist;
use App\Filament\Resources\Sistemas\Tables\SistemasTable;
use App\Models\Sistema;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SistemaResource extends Resource
{
    protected static ?string $model = Sistema::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static string|UnitEnum|null $navigationGroup = 'Fondos y Recursos Compartidos';

    protected static ?string $navigationLabel = 'Sistemas';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'sistema';

    protected static ?string $pluralModelLabel = 'sistemas';

    protected static ?string $recordTitleAttribute = 'nombre_sistema';

    public static function form(Schema $schema): Schema
    {
        return SistemaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SistemaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SistemasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AccesosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSistemas::route('/'),
            'create' => CreateSistema::route('/create'),
            'view' => ViewSistema::route('/{record}'),
            'edit' => EditSistema::route('/{record}/edit'),
        ];
    }
}
