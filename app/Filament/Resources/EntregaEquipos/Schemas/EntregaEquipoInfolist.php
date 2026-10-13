<?php

namespace App\Filament\Resources\EntregaEquipos\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class EntregaEquipoInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Formulario de entrega de equipo')
                    ->description('Documento de entrega asociado a una asignación.')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
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
                            ->label('Fecha de entrega')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->date('d/m/Y')
                            ->placeholder('—'),
                        IconEntry::make('mouse')
                            ->label('Mouse')
                            ->boolean(),
                        IconEntry::make('cooler')
                            ->label('Cooler')
                            ->boolean(),
                        IconEntry::make('audifonos')
                            ->label('Audífonos')
                            ->boolean(),
                        TextEntry::make('observaciones')
                            ->label('Observaciones')
                            ->placeholder('Sin observaciones')
                            ->columnSpanFull(),
                        TextEntry::make('nota')
                            ->label('Nota')
                            ->html()
                            ->placeholder('Sin nota')
                            ->columnSpanFull(),
                       
                    ]),
                static::registroInfolistSection(),
            ]);
    }
}
