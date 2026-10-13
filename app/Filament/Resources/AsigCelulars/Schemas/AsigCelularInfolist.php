<?php

namespace App\Filament\Resources\AsigCelulars\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use App\Models\AsigCelular;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AsigCelularInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Asignación de celular')
                    ->description('Celular entregado a un colaborador y su vigencia.')
                    ->icon(Heroicon::OutlinedDevicePhoneMobile)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('usuario.name')
                            ->label('Usuario')
                            ->icon(Heroicon::OutlinedUser)
                            ->placeholder('—'),
                        TextEntry::make('celular.imei')
                            ->label('Celular')
                            ->formatStateUsing(function (?string $state, AsigCelular $record): string {
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
                        TextEntry::make('fecha_ini')
                            ->label('Fecha de inicio')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->date('d/m/Y')
                            ->placeholder('—'),
                        TextEntry::make('fecha_fin')
                            ->label('Fecha de fin')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->date('d/m/Y')
                            ->placeholder('Vigente'),
                    ]),
                static::registroInfolistSection(),
            ]);
    }
}
