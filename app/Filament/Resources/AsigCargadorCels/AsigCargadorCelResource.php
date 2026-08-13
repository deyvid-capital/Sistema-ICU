<?php

namespace App\Filament\Resources\AsigCargadorCels;

use App\Filament\Clusters\Celulares\CelularesCluster;
use App\Filament\Resources\AsigCargadorCels\Pages\CreateAsigCargadorCel;
use App\Filament\Resources\AsigCargadorCels\Pages\EditAsigCargadorCel;
use App\Filament\Resources\AsigCargadorCels\Pages\ListAsigCargadorCels;
use App\Filament\Resources\AsigCargadorCels\Pages\ViewAsigCargadorCel;
use App\Filament\Resources\AsigCargadorCels\Schemas\AsigCargadorCelForm;
use App\Filament\Resources\AsigCargadorCels\Schemas\AsigCargadorCelInfolist;
use App\Filament\Resources\AsigCargadorCels\Tables\AsigCargadorCelsTable;
use App\Models\AsigCargadorCel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AsigCargadorCelResource extends Resource
{
    protected static ?string $model = AsigCargadorCel::class;

    protected static ?string $cluster = CelularesCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static ?string $navigationLabel = 'Asig. Cargadores de celular';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'asignación de cargador de celular';

    protected static ?string $pluralModelLabel = 'asignaciones de cargadores de celular';

    protected static ?string $recordTitleAttribute = 'cargador_serial';

    public static function form(Schema $schema): Schema
    {
        return AsigCargadorCelForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AsigCargadorCelInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AsigCargadorCelsTable::configure($table);
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
        return ['celular.id', 'cargador.serial'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAsigCargadorCels::route('/'),
            'create' => CreateAsigCargadorCel::route('/create'),
            'view' => ViewAsigCargadorCel::route('/{record}'),
            'edit' => EditAsigCargadorCel::route('/{record}/edit'),
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
