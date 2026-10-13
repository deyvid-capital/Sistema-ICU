<?php

namespace App\Filament\Resources\EntregaCelulars\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class EntregaCelularInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Formulario de entrega de celular')
                    ->description('Documento de entrega asociado a una asignación de celular.')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('asignacion.usuario.name')
                            ->label('Usuario')
                            ->icon(Heroicon::OutlinedUser)
                            ->placeholder('—'),
                        TextEntry::make('asignacion.celular.imei')
                            ->label('Celular')
                            ->formatStateUsing(function (?string $state, $record): string {
                                $celular = $record->asignacion?->celular;

                                if ($celular === null) {
                                    return '—';
                                }

                                $etiqueta = trim(($celular->marca ?? '').' '.($celular->modelo ?? ''));

                                return filled($etiqueta)
                                    ? $etiqueta.(filled($state) ? " · IMEI {$state}" : '')
                                    : (filled($state) ? "IMEI {$state}" : '#'.$celular->id);
                            })
                            ->icon(Heroicon::OutlinedDevicePhoneMobile)
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
                        TextEntry::make('observaciones')
                            ->label('Observaciones')
                            ->placeholder('Sin observaciones')
                            ->columnSpanFull(),
                        TextEntry::make('nota')
                            ->label('Nota')
                            ->placeholder('Sin nota')
                            ->columnSpanFull(),
                    ]),
                static::registroInfolistSection(),
            ]);
    }
}
