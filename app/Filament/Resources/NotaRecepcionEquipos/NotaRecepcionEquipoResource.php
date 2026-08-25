<?php

namespace App\Filament\Resources\NotaRecepcionEquipos;

use App\Filament\Clusters\Equipos\EquiposCluster;
use App\Filament\Resources\NotaRecepcionEquipos\Pages\CreateNotaRecepcionEquipo;
use App\Filament\Resources\NotaRecepcionEquipos\Pages\EditNotaRecepcionEquipo;
use App\Filament\Resources\NotaRecepcionEquipos\Pages\ListNotaRecepcionEquipos;
use App\Filament\Resources\NotaRecepcionEquipos\Pages\ViewNotaRecepcionEquipo;
use App\Filament\Resources\NotaRecepcionEquipos\RelationManagers\FirmantesRelationManager;
use App\Filament\Resources\NotaRecepcionEquipos\RelationManagers\PdfsRelationManager;
use App\Filament\Resources\NotaRecepcionEquipos\Schemas\NotaRecepcionEquipoForm;
use App\Filament\Resources\NotaRecepcionEquipos\Schemas\NotaRecepcionEquipoInfolist;
use App\Filament\Resources\NotaRecepcionEquipos\Tables\NotaRecepcionEquiposTable;
use App\Models\NotaRecepcionEquipo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NotaRecepcionEquipoResource extends Resource
{
    protected static ?string $model = NotaRecepcionEquipo::class;

    protected static ?string $cluster = EquiposCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowLeftEndOnRectangle;

    protected static ?string $navigationLabel = 'Notas de recepción';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'nota de recepción';

    protected static ?string $pluralModelLabel = 'notas de recepción';

    protected static ?string $recordTitleAttribute = 'numero';

    public static function form(Schema $schema): Schema
    {
        return NotaRecepcionEquipoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return NotaRecepcionEquipoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NotaRecepcionEquiposTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            FirmantesRelationManager::class,
            PdfsRelationManager::class,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['numero', 'nota', 'usuarioExterno.nombre'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotaRecepcionEquipos::route('/'),
            'create' => CreateNotaRecepcionEquipo::route('/create'),
            'view' => ViewNotaRecepcionEquipo::route('/{record}'),
            'edit' => EditNotaRecepcionEquipo::route('/{record}/edit'),
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
