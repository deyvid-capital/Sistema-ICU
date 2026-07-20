<?php

namespace App\Filament\Resources\UsuarioExternos;

use App\Filament\Resources\UsuarioExternos\Pages\CreateUsuarioExterno;
use App\Filament\Resources\UsuarioExternos\Pages\EditUsuarioExterno;
use App\Filament\Resources\UsuarioExternos\Pages\ListUsuarioExternos;
use App\Filament\Resources\UsuarioExternos\Pages\ViewUsuarioExterno;
use App\Filament\Resources\UsuarioExternos\RelationManagers\NotasRecepcionRelationManager;
use App\Filament\Resources\UsuarioExternos\RelationManagers\NotasSalidaRelationManager;
use App\Filament\Resources\UsuarioExternos\Schemas\UsuarioExternoForm;
use App\Filament\Resources\UsuarioExternos\Schemas\UsuarioExternoInfolist;
use App\Filament\Resources\UsuarioExternos\Tables\UsuarioExternosTable;
use App\Models\UsuarioExterno;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class UsuarioExternoResource extends Resource
{
    protected static ?string $model = UsuarioExterno::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static string|UnitEnum|null $navigationGroup = 'Formularios Entrega-Recepcion';

    protected static ?string $navigationLabel = 'Usuarios externos';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'usuario externo';

    protected static ?string $pluralModelLabel = 'usuarios externos';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return UsuarioExternoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UsuarioExternoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsuarioExternosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            NotasSalidaRelationManager::class,
            NotasRecepcionRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsuarioExternos::route('/'),
            'create' => CreateUsuarioExterno::route('/create'),
            'view' => ViewUsuarioExterno::route('/{record}'),
            'edit' => EditUsuarioExterno::route('/{record}/edit'),
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
