<?php

namespace App\Filament\Resources\RecepcionCelulars;

use App\Filament\Clusters\Celulares\CelularesCluster;
use App\Filament\Resources\RecepcionCelulars\Pages\CreateRecepcionCelular;
use App\Filament\Resources\RecepcionCelulars\Pages\EditRecepcionCelular;
use App\Filament\Resources\RecepcionCelulars\Pages\ListRecepcionCelulars;
use App\Filament\Resources\RecepcionCelulars\Pages\ViewRecepcionCelular;
use App\Filament\Resources\RecepcionCelulars\RelationManagers\FirmantesRelationManager;
use App\Filament\Resources\RecepcionCelulars\RelationManagers\PdfsRelationManager;
use App\Filament\Resources\RecepcionCelulars\Schemas\RecepcionCelularForm;
use App\Filament\Resources\RecepcionCelulars\Schemas\RecepcionCelularInfolist;
use App\Filament\Resources\RecepcionCelulars\Tables\RecepcionCelularsTable;
use App\Models\RecepcionCelular;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RecepcionCelularResource extends Resource
{
    protected static ?string $model = RecepcionCelular::class;

    protected static ?string $cluster = CelularesCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?string $navigationLabel = 'Recepciones de celular';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'recepción de celular';

    protected static ?string $pluralModelLabel = 'recepciones de celular';

    protected static ?string $recordTitleAttribute = 'numero';

    public static function form(Schema $schema): Schema
    {
        return RecepcionCelularForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RecepcionCelularInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecepcionCelularsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PdfsRelationManager::class,
            FirmantesRelationManager::class,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['numero', 'nota'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecepcionCelulars::route('/'),
            'create' => CreateRecepcionCelular::route('/create'),
            'view' => ViewRecepcionCelular::route('/{record}'),
            'edit' => EditRecepcionCelular::route('/{record}/edit'),
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
