<?php

namespace App\Filament\Resources\RecepcionEquipos\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class RecepcionEquipoInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Formulario de recepción de equipo')
                    ->description('Documento de recepción asociado a una asignación.')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('asignacion.id')
                            ->label('Asignación')
                            ->formatStateUsing(fn ($state, $record): string => filled($record->asignacion?->equipo?->service_tag)
                                ? $record->asignacion->equipo->service_tag
                                : (filled($state) ? '#'.$state : '—'))
                            ->icon(Heroicon::OutlinedComputerDesktop)
                            ->placeholder('—'),
                        TextEntry::make('numero')
                            ->label('Número de formulario')
                            ->icon(Heroicon::OutlinedHashtag)
                            ->placeholder('—'),
                        TextEntry::make('fecha')
                            ->label('Fecha de recepción')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->date('d/m/Y')
                            ->placeholder('—'),
                        TextEntry::make('observaciones')
                            ->label('Observaciones')
                            ->placeholder('Sin observaciones')
                            ->columnSpanFull(),
                    ]),
                static::registroInfolistSection(),
            ]);
    }
}
