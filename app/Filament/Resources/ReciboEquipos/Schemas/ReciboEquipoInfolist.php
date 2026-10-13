<?php

namespace App\Filament\Resources\ReciboEquipos\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use App\Models\ReciboEquipo;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ReciboEquipoInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Recibo de equipo')
                    ->description('Documento de recibo asociado a un equipo.')
                    ->icon(Heroicon::OutlinedDocumentCheck)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('equipo.service_tag')
                            ->label('Service Tag')
                            ->icon(Heroicon::OutlinedHashtag)
                            ->placeholder('—'),
                        TextEntry::make('equipo.nombre_equipo')
                            ->label('Nombre del equipo')
                            ->placeholder('—'),
                        TextEntry::make('fecha')
                            ->label('Fecha')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->date('d/m/Y')
                            ->placeholder('—'),
                        TextEntry::make('url')
                            ->label('Documento PDF')
                            ->icon(Heroicon::OutlinedDocumentArrowDown)
                            ->formatStateUsing(fn (?string $state, ReciboEquipo $record): string => $record->storedDocumentFilename() ?? '—')
                            ->url(fn (ReciboEquipo $record): ?string => $record->storedDocumentUrl())
                            ->openUrlInNewTab()
                            ->placeholder('Sin archivo')
                            ->columnSpanFull(),
                        TextEntry::make('observaciones')
                            ->label('Observaciones')
                            ->placeholder('Sin observaciones')
                            ->columnSpanFull(),
                    ]),
                static::registroInfolistSection(),
            ]);
    }
}
