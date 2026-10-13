<?php

namespace App\Filament\Resources\PdfCelulars\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use App\Models\PdfCelular;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;

class PdfCelularInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Documento PDF de celular')
                    ->description('Archivo PDF asociado a un formulario de entrega o recepción de celular.')
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
                            ->formatStateUsing(fn (?string $state, PdfCelular $record): string => $record->storedDocumentFilename() ?? '—')
                            ->url(fn (PdfCelular $record): ?string => $record->storedDocumentUrl())
                            ->openUrlInNewTab()
                            ->placeholder('Sin archivo')
                            ->columnSpanFull(),
                    ]),
                static::registroInfolistSection(withDeletedAt: false),
            ]);
    }
}
