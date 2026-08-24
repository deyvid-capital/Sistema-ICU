<?php

namespace App\Filament\Resources\AsigCelulars;

use App\Filament\Clusters\Celulares\CelularesCluster;
use App\Filament\Resources\AsigCelulars\Pages\CreateAsigCelular;
use App\Filament\Resources\AsigCelulars\Pages\EditAsigCelular;
use App\Filament\Resources\AsigCelulars\Pages\ListAsigCelulars;
use App\Filament\Resources\AsigCelulars\Pages\ViewAsigCelular;
use App\Filament\Resources\AsigCelulars\Schemas\AsigCelularForm;
use App\Filament\Resources\AsigCelulars\Schemas\AsigCelularInfolist;
use App\Filament\Resources\AsigCelulars\Tables\AsigCelularsTable;
use App\Models\AsigCelular;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AsigCelularResource extends Resource
{
    protected static ?string $model = AsigCelular::class;

    protected static ?string $cluster = CelularesCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static ?string $navigationLabel = 'Asig. celular';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'asignación de celular';

    protected static ?string $pluralModelLabel = 'asignaciones de celular';

    protected static ?string $recordTitleAttribute = 'celular_id';

    public static function form(Schema $schema): Schema
    {
        return AsigCelularForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AsigCelularInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AsigCelularsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['celular.id', 'usuario.name'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAsigCelulars::route('/'),
            'create' => CreateAsigCelular::route('/create'),
            'view' => ViewAsigCelular::route('/{record}'),
            'edit' => EditAsigCelular::route('/{record}/edit'),
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
