<?php

namespace App\Filament\Resources\PdfCelulars;

use App\Filament\Resources\PdfCelulars\Pages\CreatePdfCelular;
use App\Filament\Resources\PdfCelulars\Pages\EditPdfCelular;
use App\Filament\Resources\PdfCelulars\Pages\ListPdfCelulars;
use App\Filament\Resources\PdfCelulars\Pages\ViewPdfCelular;
use App\Filament\Resources\PdfCelulars\Schemas\PdfCelularForm;
use App\Filament\Resources\PdfCelulars\Schemas\PdfCelularInfolist;
use App\Filament\Resources\PdfCelulars\Tables\PdfCelularsTable;
use App\Models\PdfCelular;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PdfCelularResource extends Resource
{
    protected static ?string $model = PdfCelular::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocument;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'PDF de celular';

    protected static ?string $pluralModelLabel = 'PDFs de celular';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return PdfCelularForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PdfCelularInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PdfCelularsTable::configure($table);
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
            'index' => ListPdfCelulars::route('/'),
            'create' => CreatePdfCelular::route('/create'),
            'view' => ViewPdfCelular::route('/{record}'),
            'edit' => EditPdfCelular::route('/{record}/edit'),
        ];
    }
}
