<?php

namespace App\Filament\Resources\PdfCelulars\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class PdfCelularInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del documento')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('nombre')
                            ->label('Nombre'),
                        TextEntry::make('documento_type')
                            ->label('Tipo de origen')
                            ->formatStateUsing(fn (?string $state): string => class_basename((string) $state)),
                        TextEntry::make('documento_id')
                            ->label('Documento de origen')
                            ->numeric(),
                        TextEntry::make('fecha')
                            ->label('Fecha')
                            ->date('d/m/Y')
                            ->placeholder('—'),
                        TextEntry::make('url')
                            ->label('Archivo')
                            ->formatStateUsing(fn (?string $state): string => filled($state) ? basename($state) : '—'),
                        TextEntry::make('created_at')
                            ->label('Creado')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('—'),
                        TextEntry::make('updated_at')
                            ->label('Actualizado')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('—'),
                    ]),
            ]);
    }
}
