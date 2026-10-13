<?php

namespace App\Filament\Resources\PdfEquipos\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use App\Models\PdfEquipo;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;

class PdfEquipoInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Documento PDF de equipo')
                    ->description('Archivo PDF asociado a un formulario de entrega o recepción.')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('nombre')
                            ->label('Nombre')
                            ->icon(Heroicon::OutlinedDocument)
                            ->weight(FontWeight::Bold)
                            ->placeholder('—'),
                        TextEntry::make('documento_type')
                            ->label('Tipo de origen')
                            ->formatStateUsing(fn (?string $state): string => filled($state) ? class_basename($state) : '—')
                            ->placeholder('—'),
                        TextEntry::make('documento_id')
                            ->label('Documento de origen')
                            ->numeric()
                            ->placeholder('—'),
                        TextEntry::make('fecha')
                            ->label('Fecha del documento')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->date('d/m/Y')
                            ->placeholder('—'),
                        TextEntry::make('url')
                            ->label('Archivo PDF')
                            ->icon(Heroicon::OutlinedDocumentArrowDown)
                            ->formatStateUsing(fn (?string $state, PdfEquipo $record): string => $record->storedDocumentFilename() ?? '—')
                            ->url(fn (PdfEquipo $record): ?string => $record->storedDocumentUrl())
                            ->openUrlInNewTab()
                            ->placeholder('Sin archivo')
                            ->columnSpanFull(),
                    ]),
                static::registroInfolistSection(),
            ]);
    }
}
