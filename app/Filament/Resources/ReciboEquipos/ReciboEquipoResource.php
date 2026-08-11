<?php

namespace App\Filament\Resources\ReciboEquipos;

use App\Filament\Clusters\Equipos\EquiposCluster;
use App\Filament\Resources\ReciboEquipos\Pages\CreateReciboEquipo;
use App\Filament\Resources\ReciboEquipos\Pages\EditReciboEquipo;
use App\Filament\Resources\ReciboEquipos\Pages\ListReciboEquipos;
use App\Filament\Resources\ReciboEquipos\Pages\ViewReciboEquipo;
use App\Filament\Resources\ReciboEquipos\Schemas\ReciboEquipoForm;
use App\Filament\Resources\ReciboEquipos\Schemas\ReciboEquipoInfolist;
use App\Filament\Resources\ReciboEquipos\Tables\ReciboEquiposTable;
use App\Models\ReciboEquipo;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ReciboEquipoResource extends Resource
{
    protected static ?string $model = ReciboEquipo::class;

    protected static ?string $cluster = EquiposCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Recibos de equipos';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'recibo de equipo';

    protected static ?string $pluralModelLabel = 'recibos de equipos';

    protected static ?string $recordTitleAttribute = null;

    public static function form(Schema $schema): Schema
    {
        return ReciboEquipoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ReciboEquipoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReciboEquiposTable::configure($table);
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
        return ['observaciones', 'equipo.service_tag'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReciboEquipos::route('/'),
            'create' => CreateReciboEquipo::route('/create'),
            'view' => ViewReciboEquipo::route('/{record}'),
            'edit' => EditReciboEquipo::route('/{record}/edit'),
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

    public static function getDeletedNotification(ReciboEquipo $record, bool $force = false): Notification
    {
        if (! $force) {
            return Notification::make()
                ->success()
                ->title('Recibo de equipo eliminado correctamente')
                ->body('El registro se movió a la papelera. El documento PDF se conserva por si deseas restaurarlo.');
        }

        $body = $record->storedDocumentWasDeleted
            ? 'El documento PDF asociado también se eliminó del almacenamiento.'
            : 'No se encontró un archivo asociado para eliminar.';

        return Notification::make()
            ->success()
            ->title('Recibo de equipo eliminado permanentemente')
            ->body($body);
    }
}
