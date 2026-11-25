<?php

namespace App\Filament\Resources\UsuarioExternoSoporteEquipos;

use App\Filament\Clusters\Usuarios\UsuariosCluster;
use App\Filament\Resources\UsuarioExternoSoporteEquipos\Pages\CreateUsuarioExternoSoporteEquipo;
use App\Filament\Resources\UsuarioExternoSoporteEquipos\Pages\EditUsuarioExternoSoporteEquipo;
use App\Filament\Resources\UsuarioExternoSoporteEquipos\Pages\ListUsuarioExternoSoporteEquipos;
use App\Filament\Resources\UsuarioExternoSoporteEquipos\Pages\ViewUsuarioExternoSoporteEquipo;
use App\Filament\Resources\UsuarioExternoSoporteEquipos\RelationManagers\NotasRecepcionRelationManager;
use App\Filament\Resources\UsuarioExternoSoporteEquipos\RelationManagers\NotasSalidaRelationManager;
use App\Filament\Resources\UsuarioExternoSoporteEquipos\Schemas\UsuarioExternoSoporteEquipoForm;
use App\Filament\Resources\UsuarioExternoSoporteEquipos\Schemas\UsuarioExternoSoporteEquipoInfolist;
use App\Filament\Resources\UsuarioExternoSoporteEquipos\Tables\UsuarioExternoSoporteEquiposTable;
use App\Models\UsuarioExternoSoporteEquipo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UsuarioExternoSoporteEquipoResource extends Resource
{
    protected static ?string $model = UsuarioExternoSoporteEquipo::class;

    protected static ?string $cluster = UsuariosCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static ?string $navigationLabel = 'Usuarios externos de soporte técnico';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'usuario externo de soporte técnico';

    protected static ?string $pluralModelLabel = 'usuarios externos de soporte técnico';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return UsuarioExternoSoporteEquipoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UsuarioExternoSoporteEquipoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsuarioExternoSoporteEquiposTable::configure($table);
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
            'index' => ListUsuarioExternoSoporteEquipos::route('/'),
            'create' => CreateUsuarioExternoSoporteEquipo::route('/create'),
            'view' => ViewUsuarioExternoSoporteEquipo::route('/{record}'),
            'edit' => EditUsuarioExternoSoporteEquipo::route('/{record}/edit'),
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
