<?php

namespace App\Filament\Resources\CategoriasSoftware;

use App\Filament\Clusters\Software\SoftwareCluster;
use App\Filament\Resources\CategoriasSoftware\Pages\CreateCategoriaSoftware;
use App\Filament\Resources\CategoriasSoftware\Pages\EditCategoriaSoftware;
use App\Filament\Resources\CategoriasSoftware\Pages\ListCategoriasSoftware;
use App\Filament\Resources\CategoriasSoftware\Pages\ViewCategoriaSoftware;
use App\Filament\Resources\CategoriasSoftware\Schemas\CategoriaSoftwareForm;
use App\Filament\Resources\CategoriasSoftware\Schemas\CategoriaSoftwareInfolist;
use App\Filament\Resources\CategoriasSoftware\Tables\CategoriasSoftwareTable;
use App\Models\CategoriaSoftware;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CategoriaSoftwareResource extends Resource
{
    protected static ?string $model = CategoriaSoftware::class;

    protected static ?string $cluster = SoftwareCluster::class;

    protected static ?string $slug = 'categorias-software';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $navigationLabel = 'Categorías de software';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'categoría de software';

    protected static ?string $pluralModelLabel = 'categorías de software';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return CategoriaSoftwareForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CategoriaSoftwareInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoriasSoftwareTable::configure($table);
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['nombre', 'codigo', 'descripcion'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategoriasSoftware::route('/'),
            'create' => CreateCategoriaSoftware::route('/create'),
            'view' => ViewCategoriaSoftware::route('/{record}'),
            'edit' => EditCategoriaSoftware::route('/{record}/edit'),
        ];
    }
}
