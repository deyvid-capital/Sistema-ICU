<?php

namespace App\Filament\Resources\PersonalExternoSoporteTecnicos;

use App\Filament\Clusters\Equipos\EquiposCluster;
use App\Filament\Resources\PersonalExternoSoporteTecnicos\Pages\CreatePersonalExternoSoporteTecnico;
use App\Filament\Resources\PersonalExternoSoporteTecnicos\Pages\EditPersonalExternoSoporteTecnico;
use App\Filament\Resources\PersonalExternoSoporteTecnicos\Pages\ListPersonalExternoSoporteTecnicos;
use App\Filament\Resources\PersonalExternoSoporteTecnicos\Pages\ViewPersonalExternoSoporteTecnico;
use App\Filament\Resources\PersonalExternoSoporteTecnicos\RelationManagers\NotasRecepcionRelationManager;
use App\Filament\Resources\PersonalExternoSoporteTecnicos\RelationManagers\NotasSalidaRelationManager;
use App\Filament\Resources\PersonalExternoSoporteTecnicos\Schemas\PersonalExternoSoporteTecnicoForm;
use App\Filament\Resources\PersonalExternoSoporteTecnicos\Schemas\PersonalExternoSoporteTecnicoInfolist;
use App\Filament\Resources\PersonalExternoSoporteTecnicos\Tables\PersonalExternoSoporteTecnicosTable;
use App\Models\PersonalExternoSoporteTecnico;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PersonalExternoSoporteTecnicoResource extends Resource
{
    protected static ?string $model = PersonalExternoSoporteTecnico::class;

    protected static ?string $cluster = EquiposCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $navigationLabel = 'Soporte externo';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'soporte externo';

    protected static ?string $pluralModelLabel = 'soportes externos';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return PersonalExternoSoporteTecnicoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PersonalExternoSoporteTecnicoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PersonalExternoSoporteTecnicosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            NotasSalidaRelationManager::class,
            NotasRecepcionRelationManager::class,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['nombre', 'carnet', 'empresa', 'cargo'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPersonalExternoSoporteTecnicos::route('/'),
            'create' => CreatePersonalExternoSoporteTecnico::route('/create'),
            'view' => ViewPersonalExternoSoporteTecnico::route('/{record}'),
            'edit' => EditPersonalExternoSoporteTecnico::route('/{record}/edit'),
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
