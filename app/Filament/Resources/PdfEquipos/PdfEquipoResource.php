<?php

namespace App\Filament\Resources\PdfEquipos;

use App\Filament\Resources\PdfEquipos\Pages\CreatePdfEquipo;
use App\Filament\Resources\PdfEquipos\Pages\EditPdfEquipo;
use App\Filament\Resources\PdfEquipos\Pages\ListPdfEquipos;
use App\Filament\Resources\PdfEquipos\Pages\ViewPdfEquipo;
use App\Filament\Resources\PdfEquipos\Schemas\PdfEquipoForm;
use App\Filament\Resources\PdfEquipos\Schemas\PdfEquipoInfolist;
use App\Filament\Resources\PdfEquipos\Tables\PdfEquiposTable;
use App\Models\PdfEquipo;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PdfEquipoResource extends Resource
{
    protected static ?string $model = PdfEquipo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocument;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'PDF de equipo';

    protected static ?string $pluralModelLabel = 'PDFs de equipo';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return PdfEquipoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PdfEquipoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PdfEquiposTable::configure($table);
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
        return ['nombre', 'url'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPdfEquipos::route('/'),
            'create' => CreatePdfEquipo::route('/create'),
            'view' => ViewPdfEquipo::route('/{record}'),
            'edit' => EditPdfEquipo::route('/{record}/edit'),
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

    public static function getDeletedNotification(PdfEquipo $record, bool $force = false): Notification
    {
        if (! $force) {
            return Notification::make()
                ->success()
                ->title('PDF eliminado correctamente')
                ->body('El registro se movió a la papelera. El archivo se conserva por si deseas restaurarlo.');
        }

        $body = $record->storedDocumentWasDeleted
            ? 'El archivo PDF también se eliminó del almacenamiento.'
            : 'No se encontró un archivo asociado para eliminar.';

        return Notification::make()
            ->success()
            ->title('PDF eliminado permanentemente')
            ->body($body);
    }
}
