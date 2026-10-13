<?php

namespace App\Filament\Resources\AsigCargadorCels\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use App\Models\AsigCargadorCel;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AsigCargadorCelInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Asignación de cargador de celular')
                    ->description('Cargador vinculado a un celular.')
                    ->icon(Heroicon::OutlinedBolt)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('celular.imei')
                            ->label('Celular')
                            ->formatStateUsing(function (?string $state, AsigCargadorCel $record): string {
                                $celular = $record->celular;

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
                        TextEntry::make('cargador.serial')
                            ->label('Cargador')
                            ->icon(Heroicon::OutlinedHashtag)
                            ->placeholder('—'),
                        TextEntry::make('fecha')
                            ->label('Fecha')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->date('d/m/Y')
                            ->placeholder('—'),
                        TextEntry::make('motivo')
                            ->label('Motivo')
                            ->placeholder('Sin motivo')
                            ->columnSpanFull(),
                    ]),
                static::registroInfolistSection(),
            ]);
    }
}
