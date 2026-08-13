<?php

namespace App\Filament\Resources\Firmantes;

use App\Filament\Clusters\Celulares\CelularesCluster;
use App\Filament\Resources\Firmantes\Pages\CreateFirmante;
use App\Filament\Resources\Firmantes\Pages\EditFirmante;
use App\Filament\Resources\Firmantes\Pages\ListFirmantes;
use App\Filament\Resources\Firmantes\Pages\ViewFirmante;
use App\Filament\Resources\Firmantes\Schemas\FirmanteForm;
use App\Filament\Resources\Firmantes\Schemas\FirmanteInfolist;
use App\Filament\Resources\Firmantes\Tables\FirmantesTable;
use App\Models\Firmante;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FirmanteResource extends Resource
{
    protected static ?string $model = Firmante::class;

    protected static ?string $cluster = CelularesCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationLabel = 'Firmantes';

    protected static ?int $navigationSort = 7;

    protected static ?string $modelLabel = 'firmante';

    protected static ?string $pluralModelLabel = 'firmantes';

    public static function form(Schema $schema): Schema
    {
        return FirmanteForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FirmanteInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FirmantesTable::configure($table);
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
        return ['usuario.name'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFirmantes::route('/'),
            'create' => CreateFirmante::route('/create'),
            'view' => ViewFirmante::route('/{record}'),
            'edit' => EditFirmante::route('/{record}/edit'),
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
